<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Evenement;
use App\Models\Examen;
use App\Models\Faculte;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\Matricule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importe les données de l'ancienne application PHP (base itac_db) dans la nouvelle.
 * Tous les comptes reçoivent un mot de passe haché et doivent le changer à la connexion.
 */
class ImportLegacy extends Command
{
    protected $signature = 'itac:importer-ancienne-base
        {--faculte=INFO : Code de la faculté à laquelle rattacher les anciennes données}
        {--faculte-nom=Sciences informatiques : Nom de cette faculté si elle n\'existe pas}
        {--annee= : Année académique des anciennes données, ex. 2025-2026}
        {--simulation : Montre ce qui serait importé, sans rien écrire}';

    protected $description = 'Importe l\'ancienne base itac_db (connexion « legacy » dans config/database.php)';

    private array $map = ['users' => [], 'classes' => [], 'matieres' => [], 'examens' => []];

    public function handle(): int
    {
        $legacy = DB::connection('legacy');
        try {
            $legacy->getPdo();
        } catch (\Throwable $e) {
            $this->error('Connexion à l\'ancienne base impossible : '.$e->getMessage());
            $this->line('Renseignez LEGACY_DB_HOST, LEGACY_DB_DATABASE, LEGACY_DB_USERNAME et LEGACY_DB_PASSWORD dans .env.');

            return self::FAILURE;
        }

        $libelle = $this->option('annee') ?: (string) ($legacy->table('etudiants_classes')->whereNotNull('annee_academique')->value('annee_academique') ?: '2025-2026');
        if (! preg_match('/^(\d{4})-(\d{4})$/', $libelle, $m)) {
            $this->error("Année invalide : $libelle");

            return self::FAILURE;
        }

        $roles = [1 => Role::Admin, 2 => Role::Secretaire, 3 => Role::Professeur, 4 => Role::Etudiant];
        $users = $legacy->table('users')->get();
        $this->table(['Élément', 'Nombre'], [
            ['Utilisateurs', $users->count()], ['Classes', $legacy->table('classes')->count()], ['Matières', $legacy->table('matieres')->count()],
            ['Inscriptions', $legacy->table('etudiants_classes')->count()], ['Examens', $legacy->table('examens')->count()],
            ['Notes', $legacy->table('notes')->count()], ['Paiements', $legacy->table('paiements')->count()],
        ]);
        if ($this->option('simulation')) {
            $this->info('Simulation terminée : rien n\'a été écrit.');

            return self::SUCCESS;
        }
        if (! $this->confirm("Importer dans la faculté {$this->option('faculte')} pour l'année $libelle ?", true)) {
            return self::SUCCESS;
        }

        $motsDePasse = [];
        DB::transaction(function () use ($legacy, $libelle, $m, $roles, $users, &$motsDePasse) {
            $annee = AnneeAcademique::firstOrCreate(['libelle' => $libelle], ['date_debut' => $m[1].'-10-01', 'date_fin' => $m[2].'-07-31']);
            if (! AnneeAcademique::where('courante', true)->exists()) {
                $annee->definirCommeCourante();
            }
            $fac = Faculte::firstOrCreate(['code' => strtoupper($this->option('faculte'))], ['nom' => $this->option('faculte-nom'), 'couleur' => '#2F5DA8', 'seuil_reussite' => 65, 'actif' => true]);

            foreach ($users as $u) {
                $role = $roles[$u->role_id] ?? Role::Etudiant;
                $username = Str::slug($u->username ?: ($u->prenom.'.'.$u->nom), '.') ?: 'utilisateur'.$u->id;
                while (User::withTrashed()->where('username', $username)->exists()) {
                    $username .= random_int(1, 9);
                }
                $email = $u->email && filter_var($u->email, FILTER_VALIDATE_EMAIL) && ! User::where('email', $u->email)->exists() ? $u->email : null;
                $mdp = 'Itac-'.Str::upper(Str::random(4)).random_int(100, 999);
                $new = User::create([
                    'role' => $role->value, 'faculte_id' => in_array($role, [Role::Etudiant], true) ? $fac->id : null,
                    'matricule' => $role === Role::Etudiant ? Matricule::generer($fac) : null,
                    'nom' => $u->nom ?: '—', 'prenom' => $u->prenom ?: '—', 'phone' => $u->phone, 'email' => $email,
                    'username' => $username, 'password' => $mdp, 'must_change_password' => true, 'actif' => true,
                ]);
                $this->map['users'][$u->id] = $new->id;
                $motsDePasse[] = [$role->label(), $new->nom_complet, $username, $mdp];
            }

            foreach ($legacy->table('classes')->get() as $c) {
                $nom = $c->nom ?: 'Classe '.$c->id;
                $classe = Classe::firstOrCreate(['faculte_id' => $fac->id, 'nom' => $nom], ['niveau' => max(1, (int) $c->annee)]);
                $this->map['classes'][$c->id] = $classe->id;
            }
            $classeParDefaut = reset($this->map['classes']) ?: Classe::create(['faculte_id' => $fac->id, 'nom' => 'Classe importée', 'niveau' => 1])->id;

            $affectations = $legacy->table('affectations')->get();
            foreach ($legacy->table('matieres')->get() as $mt) {
                $aff = $affectations->firstWhere('matiere_id', $mt->id);
                $classeId = $aff && isset($this->map['classes'][$aff->classe_id]) ? $this->map['classes'][$aff->classe_id] : $classeParDefaut;
                $code = strtoupper($mt->code ?: 'MAT'.$mt->id);
                while (Matiere::where('code', $code)->exists()) {
                    $code .= '-'.$mt->id;
                }
                $new = Matiere::create(['classe_id' => $classeId, 'code' => $code, 'nom' => $mt->nom ?: $code, 'coefficient' => max(1, (int) $mt->coefficient), 'session' => 1]);
                $this->map['matieres'][$mt->id] = $new->id;
            }

            foreach ($affectations as $a) {
                if (isset($this->map['users'][$a->prof_id], $this->map['matieres'][$a->matiere_id])) {
                    \App\Models\Affectation::firstOrCreate(
                        ['matiere_id' => $this->map['matieres'][$a->matiere_id], 'annee_academique_id' => $annee->id],
                        ['professeur_id' => $this->map['users'][$a->prof_id]]
                    );
                }
            }

            foreach ($legacy->table('etudiants_classes')->get() as $ec) {
                if (isset($this->map['users'][$ec->etudiant_id], $this->map['classes'][$ec->classe_id])) {
                    Inscription::firstOrCreate(['etudiant_id' => $this->map['users'][$ec->etudiant_id], 'annee_academique_id' => $annee->id],
                        ['classe_id' => $this->map['classes'][$ec->classe_id], 'statut' => 'inscrit', 'date_inscription' => now()]);
                }
            }

            foreach ($legacy->table('examens')->get() as $ex) {
                $type = in_array($ex->type_exam, ['intra', 'final', 'reprise'], true) ? $ex->type_exam : 'final';
                $new = Examen::create(['annee_academique_id' => $annee->id, 'nom' => $ex->nom ?: 'Examen '.$ex->id, 'type' => $type,
                    'session' => str_contains((string) $ex->session_nom, '2') ? 2 : 1, 'date_debut' => $ex->date_debut ?: now(), 'date_fin' => $ex->date_fin ?: ($ex->date_debut ?: now()),
                    'notes_publiees' => true, 'publie_at' => now()]);
                $this->map['examens'][$ex->id] = $new->id;
                Evenement::create(['examen_id' => $new->id, 'titre' => $new->nom, 'type' => $type === 'reprise' ? 'reprise' : 'examen', 'date_debut' => $new->date_debut, 'date_fin' => $new->date_fin]);
            }

            foreach ($legacy->table('notes')->get() as $n) {
                if (isset($this->map['users'][$n->etudiant_id], $this->map['matieres'][$n->matiere_id], $this->map['examens'][$n->exam_id])) {
                    Note::updateOrCreate(['examen_id' => $this->map['examens'][$n->exam_id], 'matiere_id' => $this->map['matieres'][$n->matiere_id], 'etudiant_id' => $this->map['users'][$n->etudiant_id]],
                        ['valeur' => min(100, max(0, (float) $n->note))]);
                }
            }

            foreach ($legacy->table('paiements')->get() as $p) {
                if (isset($this->map['users'][$p->etudiant_id]) && (float) $p->montant > 0) {
                    Paiement::create(['numero_recu' => FinanceService::prochainNumeroRecu(), 'etudiant_id' => $this->map['users'][$p->etudiant_id], 'annee_academique_id' => $annee->id,
                        'tranche_numero' => $p->versement_num ?: null, 'montant' => $p->montant, 'date_paiement' => $p->date_payment ?: now(),
                        'methode' => 'especes', 'reference' => $p->method ? Str::limit($p->method, 95) : null, 'note' => $p->note ? Str::limit($p->note, 250) : null]);
                }
            }

            foreach ($legacy->table('calendar_events')->get() as $ev) {
                $type = match (true) { str_contains((string) $ev->type_event, 'exam') => 'examen', str_contains((string) $ev->type_event, 'repr') => 'reprise', str_contains((string) $ev->type_event, 'cong') || str_contains((string) $ev->type_event, 'vac') => 'conge', default => 'autre' };
                if ($ev->type_event === 'examen') {
                    continue; // déjà recréés à partir des examens
                }
                Evenement::create(['titre' => $ev->titre ?: 'Événement', 'description' => $ev->description, 'type' => $type, 'date_debut' => $ev->date_debut ?: now(), 'date_fin' => $ev->date_fin ?: ($ev->date_debut ?: now())]);
            }
        });

        $fichier = storage_path('app/private/import-mots-de-passe-'.now()->format('Ymd-His').'.csv');
        @mkdir(dirname($fichier), 0750, true);
        $fh = fopen($fichier, 'w');
        fputcsv($fh, ['Rôle', 'Nom', 'Identifiant', 'Mot de passe provisoire'], ';');
        foreach ($motsDePasse as $row) {
            fputcsv($fh, $row, ';');
        }
        fclose($fh);

        $this->info('Import terminé.');
        $this->warn("Les mots de passe provisoires sont dans : $fichier");
        $this->warn('Distribuez-les puis SUPPRIMEZ ce fichier.');

        return self::SUCCESS;
    }
}
