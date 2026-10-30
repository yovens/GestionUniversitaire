<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Affectation;
use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Evenement;
use App\Models\Examen;
use App\Models\Faculte;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\TrancheFrais;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\Matricule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Données de démonstration (mot de passe de tous les comptes : « password1 »). */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $annee = AnneeAcademique::create(['libelle' => '2026-2027', 'date_debut' => '2026-10-01', 'date_fin' => '2027-07-31']);
            $annee->definirCommeCourante();

            $mdp = 'password1';
            $staff = fn (string $role, string $u, string $p, string $n, ?int $fac = null) => User::create([
                'role' => $role, 'faculte_id' => $fac, 'prenom' => $p, 'nom' => $n, 'username' => $u,
                'password' => $mdp, 'must_change_password' => false, 'actif' => true, 'phone' => '+509 3'.random_int(1000000, 9999999),
            ]);

            $staff('admin', 'admin', 'Marie', 'Joseph');
            $staff('secretaire', 'secretariat', 'Nadège', 'Pierre-Louis');

            $facultes = [
                ['INFO', 'Sciences informatiques', '#2F5DA8', ['Algorithmique', 'Programmation web', 'Bases de données', 'Réseaux', 'Mathématiques discrètes', 'Systèmes d’exploitation']],
                ['GC', 'Génie civil', '#C2410C', ['Résistance des matériaux', 'Topographie', 'Dessin technique', 'Béton armé', 'Mécanique des sols', 'Hydraulique']],
                ['AGRO', 'Agronomie', '#15803D', ['Pédologie', 'Botanique', 'Zootechnie', 'Agroéconomie', 'Phytotechnie', 'Irrigation']],
                ['ADM', 'Sciences administratives', '#7C3AED', ['Comptabilité générale', 'Droit des affaires', 'Gestion des ressources humaines', 'Économie', 'Marketing', 'Statistiques']],
            ];

            $profs = collect(range(1, 8))->map(fn ($i) => $staff('professeur', "prof$i", ['Jean', 'Rose', 'Wilson', 'Guerline', 'Frantz', 'Myrlande', 'Patrick', 'Sandra'][$i - 1], ['Dorval', 'Augustin', 'Célestin', 'Jean-Baptiste', 'Michel', 'Désir', 'Étienne', 'Charles'][$i - 1]));
            $prenoms = ['Kervens', 'Mirlande', 'Jameson', 'Fabiola', 'Wideline', 'Ricardo', 'Sherly', 'Dieuveut', 'Nathalie', 'Peterson', 'Esther', 'Junior', 'Lovely', 'Stanley', 'Roseline', 'Wilner'];
            $noms = ['Louis', 'Jean', 'Pierre', 'Joseph', 'Charles', 'François', 'Paul', 'Baptiste', 'Lafleur', 'Noël', 'Alexis', 'Fils-Aimé'];

            $examensCommuns = [
                Examen::create(['annee_academique_id' => $annee->id, 'nom' => 'Intra session 1', 'type' => 'intra', 'session' => 1, 'date_debut' => '2026-12-07', 'date_fin' => '2026-12-12', 'tranche_requise' => 1, 'notes_publiees' => true, 'publie_at' => now()]),
                Examen::create(['annee_academique_id' => $annee->id, 'nom' => 'Final session 1', 'type' => 'final', 'session' => 1, 'date_debut' => '2027-02-08', 'date_fin' => '2027-02-13', 'tranche_requise' => 2]),
            ];
            foreach ($examensCommuns as $e) {
                Evenement::create(['examen_id' => $e->id, 'titre' => $e->nom, 'type' => 'examen', 'date_debut' => $e->date_debut, 'date_fin' => $e->date_fin, 'description' => 'Examen '.$e->type_label]);
            }
            Evenement::create(['titre' => 'Rentrée universitaire', 'type' => 'cours', 'date_debut' => '2026-10-05', 'date_fin' => '2026-10-05']);
            Evenement::create(['titre' => 'Vacances de Noël', 'type' => 'conge', 'date_debut' => '2026-12-21', 'date_fin' => '2027-01-06']);
            Evenement::create(['titre' => 'Fête de l’Indépendance', 'type' => 'conge', 'date_debut' => '2027-01-01', 'date_fin' => '2027-01-01']);

            $p = 0;
            foreach ($facultes as $fi => [$code, $nom, $couleur, $cours]) {
                $f = Faculte::create(['code' => $code, 'nom' => $nom, 'couleur' => $couleur, 'seuil_reussite' => 65, 'actif' => true]);
                $staff('doyen', 'doyen.'.strtolower($code), 'Doyen', $nom, $f->id);
                foreach ([[1, 40000], [2, 35000], [3, 25000]] as [$n, $montant]) {
                    TrancheFrais::create(['faculte_id' => $f->id, 'annee_academique_id' => $annee->id, 'numero' => $n, 'libelle' => "Versement $n", 'montant' => $montant,
                        'date_limite' => ['2026-11-30', '2027-01-31', '2027-04-30'][$n - 1]]);
                }
                foreach ([1, 2] as $niveau) {
                    $classe = Classe::create(['faculte_id' => $f->id, 'nom' => "L$niveau $nom", 'niveau' => $niveau]);
                    $matieres = collect($cours)->map(fn ($c, $ci) => Matiere::create([
                        'classe_id' => $classe->id, 'code' => $code.$niveau.'-'.str_pad((string) ($ci + 1), 2, '0', STR_PAD_LEFT),
                        'nom' => $c.($niveau > 1 ? ' II' : ''), 'coefficient' => [3, 3, 2, 2, 2, 1][$ci], 'session' => $ci < 3 ? 1 : 2,
                    ]));
                    foreach ($matieres as $m) {
                        Affectation::create(['professeur_id' => $profs[$p++ % $profs->count()]->id, 'matiere_id' => $m->id, 'annee_academique_id' => $annee->id]);
                    }
                    for ($k = 0; $k < 8; $k++) {
                        $etu = User::create([
                            'role' => Role::Etudiant->value, 'faculte_id' => $f->id, 'matricule' => Matricule::generer($f),
                            'prenom' => $prenoms[($k + $fi * 3 + $niveau) % count($prenoms)], 'nom' => $noms[($k * 5 + $fi + $niveau) % count($noms)],
                            'sexe' => $k % 2 ? 'F' : 'M', 'password' => $mdp, 'must_change_password' => false, 'actif' => true,
                            'phone' => '+509 4'.random_int(1000000, 9999999), 'date_naissance' => now()->subYears(19 + $niveau)->subDays(random_int(1, 300)),
                            'username' => 'tmp'.uniqid(),
                        ]);
                        $etu->update(['username' => strtolower($etu->matricule)]);
                        Inscription::create(['etudiant_id' => $etu->id, 'classe_id' => $classe->id, 'annee_academique_id' => $annee->id, 'statut' => 'inscrit', 'date_inscription' => '2026-10-02']);
                        // Paiements variés : certains à jour, d'autres en retard
                        $montants = [[40000, 35000], [40000], [25000], [40000, 35000, 25000], []][$k % 5];
                        foreach ($montants as $ti => $mt) {
                            Paiement::create(['numero_recu' => FinanceService::prochainNumeroRecu(), 'etudiant_id' => $etu->id, 'annee_academique_id' => $annee->id,
                                'tranche_numero' => $ti + 1, 'montant' => $mt, 'date_paiement' => now()->subDays(random_int(5, 20)), 'methode' => ['especes', 'moncash', 'depot'][$ti % 3]]);
                        }
                        foreach ($matieres->where('session', 1) as $m) {
                            Note::create(['examen_id' => $examensCommuns[0]->id, 'matiere_id' => $m->id, 'etudiant_id' => $etu->id, 'valeur' => random_int(48, 98)]);
                        }
                    }
                }
            }
        });

        $this->command?->info('Démo prête. Comptes : admin, secretariat, doyen.info, prof1…prof8, info-26-0001… — mot de passe : password1');
    }
}
