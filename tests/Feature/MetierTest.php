<?php

namespace Tests\Feature;

use App\Models\Affectation;
use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Examen;
use App\Models\Faculte;
use App\Models\Inscription;
use App\Models\JournalActivite;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\TrancheFrais;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\ResultatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MetierTest extends TestCase
{
    use RefreshDatabase;

    private AnneeAcademique $annee;
    private Faculte $info;
    private Faculte $gc;
    private Classe $classe;
    private Matiere $algo;
    private Matiere $web;
    private User $admin;
    private User $doyenInfo;
    private User $prof;
    private User $autreProf;
    private User $etu;
    private Inscription $insc;
    private Examen $intra;
    private Examen $final;

    protected function setUp(): void
    {
        parent::setUp();
        $this->annee = AnneeAcademique::create(['libelle' => '2026-2027', 'date_debut' => '2026-10-01', 'date_fin' => '2027-07-31']);
        $this->annee->definirCommeCourante();
        $this->info = Faculte::create(['code' => 'INFO', 'nom' => 'Informatique', 'couleur' => '#2F5DA8', 'seuil_reussite' => 65]);
        $this->gc = Faculte::create(['code' => 'GC', 'nom' => 'Génie civil', 'couleur' => '#C2410C', 'seuil_reussite' => 65]);
        $this->classe = Classe::create(['faculte_id' => $this->info->id, 'nom' => 'L1 Info', 'niveau' => 1]);
        $this->algo = Matiere::create(['classe_id' => $this->classe->id, 'code' => 'ALGO', 'nom' => 'Algorithmique', 'coefficient' => 3, 'session' => 1]);
        $this->web = Matiere::create(['classe_id' => $this->classe->id, 'code' => 'WEB', 'nom' => 'Web', 'coefficient' => 1, 'session' => 1]);

        $mk = fn ($role, $u, $fac = null) => User::create(['role' => $role, 'username' => $u, 'nom' => ucfirst($u), 'prenom' => 'Test', 'password' => 'secret123', 'must_change_password' => false, 'faculte_id' => $fac]);
        $this->admin = $mk('admin', 'admin');
        $this->doyenInfo = $mk('doyen', 'doyen', $this->info->id);
        $this->prof = $mk('professeur', 'prof');
        $this->autreProf = $mk('professeur', 'prof2');
        $this->etu = $mk('etudiant', 'etu', $this->info->id);
        $this->insc = Inscription::create(['etudiant_id' => $this->etu->id, 'classe_id' => $this->classe->id, 'annee_academique_id' => $this->annee->id, 'date_inscription' => now()]);
        Affectation::create(['professeur_id' => $this->prof->id, 'matiere_id' => $this->algo->id, 'annee_academique_id' => $this->annee->id]);
        Affectation::create(['professeur_id' => $this->autreProf->id, 'matiere_id' => $this->web->id, 'annee_academique_id' => $this->annee->id]);
        $this->intra = Examen::create(['annee_academique_id' => $this->annee->id, 'nom' => 'Intra', 'type' => 'intra', 'session' => 1, 'date_debut' => now(), 'date_fin' => now(), 'tranche_requise' => 1]);
        $this->final = Examen::create(['annee_academique_id' => $this->annee->id, 'nom' => 'Final', 'type' => 'final', 'session' => 1, 'date_debut' => now(), 'date_fin' => now()]);
        TrancheFrais::create(['faculte_id' => $this->info->id, 'annee_academique_id' => $this->annee->id, 'numero' => 1, 'libelle' => 'V1', 'montant' => 1000]);
        TrancheFrais::create(['faculte_id' => $this->info->id, 'annee_academique_id' => $this->annee->id, 'numero' => 2, 'libelle' => 'V2', 'montant' => 2000]);
    }

    // ---------- Authentification ----------

    public function test_connexion_mot_de_passe_hache_et_premiere_connexion(): void
    {
        $u = User::create(['role' => 'etudiant', 'username' => 'nouveau', 'nom' => 'N', 'prenom' => 'P', 'password' => 'Provisoire1', 'must_change_password' => true]);
        $this->assertTrue(Hash::check('Provisoire1', $u->fresh()->password));
        $this->assertNotSame('Provisoire1', $u->fresh()->password);

        $this->post('/connexion', ['username' => 'nouveau', 'password' => 'Provisoire1'])->assertRedirect('/tableau-de-bord');
        $this->get('/tableau-de-bord')->assertRedirect('/mot-de-passe');
        $this->put('/mot-de-passe', ['current_password' => 'Provisoire1', 'password' => 'NouveauMdp2', 'password_confirmation' => 'NouveauMdp2'])->assertRedirect('/tableau-de-bord');
        $this->get('/tableau-de-bord')->assertOk();
    }

    public function test_connexion_refusee_et_limitee(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/connexion', ['username' => 'admin', 'password' => 'mauvais'])->assertSessionHasErrors('username');
        }
        $this->post('/connexion', ['username' => 'admin', 'password' => 'secret123'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_compte_desactive_est_deconnecte(): void
    {
        $this->etu->update(['actif' => false]);
        $this->actingAs($this->etu)->get('/tableau-de-bord')->assertRedirect('/connexion');
        $this->assertGuest();
    }

    public function test_connexion_par_matricule(): void
    {
        $this->etu->update(['matricule' => 'INFO-26-0099']);
        $this->post('/connexion', ['username' => 'INFO-26-0099', 'password' => 'secret123'])->assertRedirect('/tableau-de-bord');
        $this->assertAuthenticatedAs($this->etu);
    }

    // ---------- Cloisonnement des facultés ----------

    public function test_doyen_ne_voit_pas_une_autre_faculte(): void
    {
        $etuGc = User::create(['role' => 'etudiant', 'username' => 'gc1', 'nom' => 'G', 'prenom' => 'C', 'password' => 'x12345678', 'faculte_id' => $this->gc->id, 'must_change_password' => false]);
        $this->actingAs($this->doyenInfo)->get("/etudiants/{$etuGc->id}")->assertForbidden();
        $this->actingAs($this->doyenInfo)->get("/etudiants/{$this->etu->id}")->assertOk();
        $this->actingAs($this->doyenInfo)->post('/classes', ['faculte_id' => $this->gc->id, 'nom' => 'L1 GC', 'niveau' => 1])->assertForbidden();
        $this->actingAs($this->doyenInfo)->get('/etudiants')->assertOk()->assertDontSee('gc1');
    }

    // ---------- Calcul des résultats ----------

    public function test_moyenne_ponderee_reprise_et_decision(): void
    {
        $reprise = Examen::create(['annee_academique_id' => $this->annee->id, 'nom' => 'Reprise', 'type' => 'reprise', 'session' => 1, 'date_debut' => now(), 'date_fin' => now()]);
        Note::create(['examen_id' => $this->intra->id, 'matiere_id' => $this->algo->id, 'etudiant_id' => $this->etu->id, 'valeur' => 50]);
        Note::create(['examen_id' => $this->final->id, 'matiere_id' => $this->algo->id, 'etudiant_id' => $this->etu->id, 'valeur' => 80]);
        Note::create(['examen_id' => $this->intra->id, 'matiere_id' => $this->web->id, 'etudiant_id' => $this->etu->id, 'valeur' => 40]);
        Note::create(['examen_id' => $this->final->id, 'matiere_id' => $this->web->id, 'etudiant_id' => $this->etu->id, 'valeur' => 50]);

        $b = app(ResultatService::class)->bulletin($this->insc->fresh());
        $algo = $b['lignes']->firstWhere('matiere.id', $this->algo->id);
        $web = $b['lignes']->firstWhere('matiere.id', $this->web->id);
        $this->assertEqualsWithDelta(68.0, $algo['moyenne'], 0.001); // 50*0.4 + 80*0.6
        $this->assertEqualsWithDelta(46.0, $web['moyenne'], 0.001);  // 40*0.4 + 50*0.6
        $this->assertEqualsWithDelta((68 * 3 + 46) / 4, $b['moyenne_generale'], 0.01);
        $this->assertSame('reprise', $b['decision_code']);

        Note::create(['examen_id' => $reprise->id, 'matiere_id' => $this->web->id, 'etudiant_id' => $this->etu->id, 'valeur' => 75]);
        $b = app(ResultatService::class)->bulletin($this->insc->fresh());
        $this->assertEqualsWithDelta(75.0, $b['lignes']->firstWhere('matiere.id', $this->web->id)['moyenne'], 0.001);
        $this->assertSame('admis', $b['decision_code']);
    }

    public function test_absent_compte_zero(): void
    {
        Note::create(['examen_id' => $this->intra->id, 'matiere_id' => $this->algo->id, 'etudiant_id' => $this->etu->id, 'valeur' => null, 'absent' => true]);
        Note::create(['examen_id' => $this->final->id, 'matiere_id' => $this->algo->id, 'etudiant_id' => $this->etu->id, 'valeur' => 100]);
        $b = app(ResultatService::class)->bulletin($this->insc->fresh());
        $this->assertEqualsWithDelta(60.0, $b['lignes']->firstWhere('matiere.id', $this->algo->id)['moyenne'], 0.001);
    }

    // ---------- Paiements et accès aux notes ----------

    public function test_notes_masquees_tant_que_le_versement_nest_pas_regle(): void
    {
        Note::create(['examen_id' => $this->intra->id, 'matiere_id' => $this->algo->id, 'etudiant_id' => $this->etu->id, 'valeur' => 88]);
        $this->intra->update(['notes_publiees' => true]);
        $finance = app(FinanceService::class);

        $this->assertFalse($finance->peutVoirNotes($this->etu, $this->intra->fresh()));
        $this->actingAs($this->etu)->get('/etudiant/notes')->assertOk()->assertSee('Paiement requis')->assertDontSee('88,00');

        $this->actingAs($this->admin)->post('/paiements', [
            'etudiant_id' => $this->etu->id, 'montant' => 1000, 'tranche_numero' => 1, 'date_paiement' => today()->format('Y-m-d'), 'methode' => 'moncash',
        ])->assertRedirect();
        $finance->oublier();
        $this->assertTrue($finance->peutVoirNotes($this->etu, $this->intra->fresh()));
        $this->actingAs($this->etu)->get('/etudiant/notes')->assertOk()->assertSee('88,00');
    }

    public function test_notes_non_publiees_invisibles_pour_etudiant(): void
    {
        Note::create(['examen_id' => $this->final->id, 'matiere_id' => $this->algo->id, 'etudiant_id' => $this->etu->id, 'valeur' => 77]);
        $this->actingAs($this->etu)->get('/etudiant/notes')->assertOk()->assertDontSee('77,00');
        $this->final->update(['notes_publiees' => true]);
        $this->actingAs($this->etu)->get('/etudiant/notes')->assertOk()->assertSee('77,00');
    }

    public function test_recus_sequentiels_et_annulation(): void
    {
        foreach ([500, 700] as $m) {
            $this->actingAs($this->admin)->post('/paiements', ['etudiant_id' => $this->etu->id, 'montant' => $m, 'date_paiement' => today()->format('Y-m-d'), 'methode' => 'especes'])->assertRedirect();
        }
        $recus = Paiement::orderBy('id')->pluck('numero_recu')->all();
        $this->assertSame(['REC-'.date('Y').'-000001', 'REC-'.date('Y').'-000002'], $recus);

        $s = app(FinanceService::class)->situation($this->etu, $this->annee);
        $this->assertEqualsWithDelta(1200, $s['total_paye'], 0.01);
        $this->assertEqualsWithDelta(1800, $s['solde'], 0.01);

        $p = Paiement::first();
        $this->actingAs($this->admin)->post("/paiements/{$p->id}/annuler", ['motif_annulation' => 'Erreur de saisie'])->assertRedirect();
        app(FinanceService::class)->oublier();
        $this->assertEqualsWithDelta(700, app(FinanceService::class)->situation($this->etu, $this->annee)['total_paye'], 0.01);
        $this->assertNotNull($p->fresh()->annule_at);
        $this->assertTrue(JournalActivite::where('action', 'paiement.annulation')->exists());
    }

    public function test_paiement_refuse_un_fichier_dangereux(): void
    {
        $file = UploadedFile::fake()->createWithContent('recu.php', '<?php echo 1;');
        $this->actingAs($this->admin)->post('/paiements', [
            'etudiant_id' => $this->etu->id, 'montant' => 100, 'date_paiement' => today()->format('Y-m-d'), 'methode' => 'especes', 'justificatif' => $file,
        ])->assertSessionHasErrors('justificatif');
        $this->assertSame(0, Paiement::count());
    }

    public function test_paiement_futur_ou_negatif_refuse(): void
    {
        $this->actingAs($this->admin)->post('/paiements', ['etudiant_id' => $this->etu->id, 'montant' => -5, 'date_paiement' => now()->addDays(3)->format('Y-m-d'), 'methode' => 'especes'])
            ->assertSessionHasErrors(['montant', 'date_paiement']);
    }

    // ---------- Saisie des notes ----------

    public function test_professeur_saisit_seulement_ses_cours_et_avant_publication(): void
    {
        $payload = fn ($m) => ['examen_id' => $this->intra->id, 'matiere_id' => $m->id, 'notes' => [$this->etu->id => '72,5']];

        $this->actingAs($this->prof)->post('/notes/saisie', $payload($this->algo))->assertRedirect();
        $this->assertEquals(72.5, (float) Note::where('matiere_id', $this->algo->id)->value('valeur'));

        $this->actingAs($this->prof)->post('/notes/saisie', $payload($this->web))->assertForbidden();

        $this->intra->update(['notes_publiees' => true]);
        $this->actingAs($this->prof)->post('/notes/saisie', $payload($this->algo))->assertForbidden();
        $this->actingAs($this->prof)->get("/notes/saisie?examen={$this->intra->id}&matiere={$this->algo->id}")->assertOk()->assertSee('lecture seule');

        // le secrétariat peut corriger après publication, et c'est journalisé
        $this->actingAs($this->doyenInfo)->post('/notes/saisie', ['examen_id' => $this->intra->id, 'matiere_id' => $this->algo->id, 'notes' => [$this->etu->id => '80']])->assertRedirect();
        $this->assertEquals(80.0, (float) Note::where('matiere_id', $this->algo->id)->value('valeur'));
        $log = JournalActivite::where('action', 'notes.saisie')->latest('id')->first();
        $this->assertStringContainsString('après publication', $log->description);
        $this->assertEquals(72.5, $log->details['changements'][0]['avant']);
    }

    public function test_note_hors_bareme_refusee_et_etudiant_etranger_ignore(): void
    {
        $intrus = User::create(['role' => 'etudiant', 'username' => 'intrus', 'nom' => 'I', 'prenom' => 'X', 'password' => 'x12345678', 'faculte_id' => $this->info->id, 'must_change_password' => false]);
        $this->actingAs($this->prof)->post('/notes/saisie', ['examen_id' => $this->intra->id, 'matiere_id' => $this->algo->id, 'notes' => [$this->etu->id => '120']])
            ->assertSessionHasErrors();
        $this->actingAs($this->prof)->post('/notes/saisie', ['examen_id' => $this->intra->id, 'matiere_id' => $this->algo->id, 'notes' => [$intrus->id => '90', $this->etu->id => '']]);
        $this->assertSame(0, Note::count());
    }

    public function test_vider_une_case_supprime_la_note(): void
    {
        Note::create(['examen_id' => $this->intra->id, 'matiere_id' => $this->algo->id, 'etudiant_id' => $this->etu->id, 'valeur' => 50]);
        $this->actingAs($this->prof)->post('/notes/saisie', ['examen_id' => $this->intra->id, 'matiere_id' => $this->algo->id, 'notes' => [$this->etu->id => '']]);
        $this->assertSame(0, Note::count());
    }

    // ---------- Inscriptions ----------

    public function test_inscription_cree_matricule_compte_et_inscription(): void
    {
        $r = $this->actingAs($this->doyenInfo)->post('/etudiants', ['nom' => 'Pierre', 'prenom' => 'Jean', 'classe_id' => $this->classe->id, 'sexe' => 'M']);
        $r->assertSessionHasNoErrors();
        $e = User::where('nom', 'Pierre')->firstOrFail();
        $r->assertRedirect("/etudiants/{$e->id}")->assertSessionHas('identifiants');
        $this->assertSame('INFO-'.now()->format('y').'-0001', $e->matricule);
        $this->assertSame(strtolower($e->matricule), $e->username);
        $this->assertTrue($e->must_change_password);
        $this->assertTrue(Inscription::where('etudiant_id', $e->id)->where('annee_academique_id', $this->annee->id)->exists());

        $this->actingAs($this->doyenInfo)->post("/etudiants/{$e->id}/reinscrire", ['classe_id' => $this->classe->id])->assertSessionHas('error');
    }

    public function test_dossier_avec_paiement_ne_peut_pas_etre_supprime(): void
    {
        Paiement::create(['numero_recu' => 'REC-X', 'etudiant_id' => $this->etu->id, 'annee_academique_id' => $this->annee->id, 'montant' => 10, 'date_paiement' => now(), 'methode' => 'especes']);
        $this->actingAs($this->admin)->delete("/etudiants/{$this->etu->id}")->assertSessionHas('error');
        $this->assertNotNull($this->etu->fresh());
    }

    public function test_suppression_classe_avec_cours_refusee(): void
    {
        $this->actingAs($this->admin)->delete("/classes/{$this->classe->id}")->assertSessionHas('error');
        $this->assertNotNull($this->classe->fresh());
    }

    // ---------- Sécurité HTTP ----------

    public function test_entetes_de_securite_et_csrf(): void
    {
        $r = $this->get('/connexion');
        $r->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString("default-src 'self'", $r->headers->get('Content-Security-Policy'));
    }

    public function test_fichier_prive_inaccessible_a_un_autre_etudiant(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->actingAs($this->admin)->post('/paiements', [
            'etudiant_id' => $this->etu->id, 'montant' => 100, 'date_paiement' => today()->format('Y-m-d'), 'methode' => 'especes',
            'justificatif' => UploadedFile::fake()->create('preuve.pdf', 20, 'application/pdf'),
        ]);
        $p = Paiement::firstOrFail();
        $autre = User::create(['role' => 'etudiant', 'username' => 'autre', 'nom' => 'A', 'prenom' => 'B', 'password' => 'x12345678', 'faculte_id' => $this->info->id, 'must_change_password' => false]);
        $this->actingAs($autre)->get("/fichiers/justificatif/{$p->id}")->assertForbidden();
        $this->actingAs($this->doyenInfo)->get("/fichiers/justificatif/{$p->id}")->assertOk();
    }
}
