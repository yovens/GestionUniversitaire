<?php

namespace Tests\Feature;

use App\Models\Affectation;
use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Evenement;
use App\Models\Examen;
use App\Models\Faculte;
use App\Models\Matiere;
use App\Models\SujetExamen;
use App\Models\TrancheFrais;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Parcours complet de mise en place d'une université, par les formulaires. */
class FormulairesTest extends TestCase
{
    use RefreshDatabase;

    public function test_mise_en_place_complete_par_ladministration(): void
    {
        Storage::fake('local');
        $this->artisan('itac:installer', ['--password' => 'MotDePasse1', '--annee' => '2026-2027'])->assertSuccessful();
        $admin = User::where('username', 'admin')->firstOrFail();
        $this->assertTrue(AnneeAcademique::courante()->libelle === '2026-2027');

        $as = $this->actingAs($admin);
        $as->post('/admin/facultes', ['code' => 'gc', 'nom' => 'Génie civil', 'couleur' => '#C2410C', 'seuil_reussite' => 65, 'actif' => 1])->assertRedirect('/admin/facultes');
        $f = Faculte::where('code', 'GC')->firstOrFail();
        $as->put("/admin/facultes/{$f->id}", ['code' => 'GC', 'nom' => 'Génie civil et architecture', 'couleur' => '#C2410C', 'seuil_reussite' => 60, 'actif' => 1])->assertRedirect();
        $as->post('/admin/facultes', ['code' => 'GC', 'nom' => 'Doublon', 'couleur' => '#000000', 'seuil_reussite' => 65])->assertSessionHasErrors('code');

        $as->post('/admin/annees', ['libelle' => '2027-2028', 'date_debut' => '2027-10-01', 'date_fin' => '2028-07-31'])->assertRedirect();
        $this->assertSame('2026-2027', AnneeAcademique::courante()->libelle);

        $as->post('/admin/utilisateurs', ['role' => 'professeur', 'nom' => 'Dorval', 'prenom' => 'Jean', 'username' => 'jdorval'])->assertRedirect()->assertSessionHas('identifiants');
        $as->post('/admin/utilisateurs', ['role' => 'doyen', 'nom' => 'X', 'prenom' => 'Y', 'username' => 'doyengc'])->assertSessionHasErrors('faculte_id');
        $as->post('/admin/utilisateurs', ['role' => 'doyen', 'nom' => 'X', 'prenom' => 'Y', 'username' => 'doyengc', 'faculte_id' => $f->id])->assertRedirect();
        $prof = User::where('username', 'jdorval')->firstOrFail();
        $this->assertTrue($prof->must_change_password);

        $as->post('/classes', ['faculte_id' => $f->id, 'nom' => 'L1 GC', 'niveau' => 1])->assertRedirect();
        $classe = Classe::firstOrFail();
        $as->post('/matieres', ['classe_id' => $classe->id, 'code' => 'gc-rdm', 'nom' => 'RDM', 'coefficient' => 3, 'session' => 1])->assertRedirect();
        $m = Matiere::where('code', 'GC-RDM')->firstOrFail();
        $this->assertSame($f->id, $m->faculte_id);
        $as->post('/affectations', ['matiere_id' => $m->id, 'professeur_id' => $prof->id])->assertRedirect();
        $this->assertTrue(Affectation::where('matiere_id', $m->id)->exists());
        $as->post('/affectations', ['matiere_id' => $m->id, 'professeur_id' => $admin->id])->assertSessionHasErrors('professeur_id');

        $as->post('/frais', ['faculte_id' => $f->id, 'numero' => 1, 'libelle' => 'Versement 1', 'montant' => 25000, 'date_limite' => '2026-11-30'])->assertRedirect();
        $this->assertSame(1, TrancheFrais::count());

        $as->post('/examens', ['nom' => 'Intra S1', 'type' => 'intra', 'session' => 1, 'faculte_id' => $f->id, 'date_debut' => '2026-12-07', 'date_fin' => '2026-12-12', 'tranche_requise' => 1])->assertRedirect('/examens');
        $examen = Examen::firstOrFail();
        $this->assertTrue(Evenement::where('examen_id', $examen->id)->exists(), "L'examen doit apparaître au calendrier");
        $as->put("/examens/{$examen->id}", ['nom' => 'Intra S1 (modifié)', 'type' => 'intra', 'session' => 1, 'faculte_id' => $f->id, 'date_debut' => '2026-12-08', 'date_fin' => '2026-12-12'])->assertRedirect();
        $this->assertSame('Intra S1 (modifié)', Evenement::where('examen_id', $examen->id)->value('titre'));
        $as->post('/examens', ['nom' => 'Mauvais', 'type' => 'intra', 'session' => 1, 'date_debut' => '2026-12-12', 'date_fin' => '2026-12-01'])->assertSessionHasErrors('date_fin');

        $as->post('/calendrier', ['titre' => 'Rentrée', 'type' => 'cours', 'date_debut' => '2026-10-05', 'date_fin' => '2026-10-05'])->assertRedirect();
        $ev = Evenement::where('titre', 'Rentrée')->firstOrFail();
        $as->put("/calendrier/{$ev->id}", ['titre' => 'Rentrée officielle', 'type' => 'cours', 'date_debut' => '2026-10-05', 'date_fin' => '2026-10-06'])->assertRedirect();
        $as->delete('/calendrier/'.Evenement::where('examen_id', $examen->id)->value('id'))->assertSessionHas('error');

        // Inscription + paiement + reçu
        $as->post('/etudiants', ['nom' => 'Louis', 'prenom' => 'Kervens', 'classe_id' => $classe->id, 'photo' => UploadedFile::fake()->image('p.jpg', 200, 200)])->assertRedirect();
        $etu = User::where('nom', 'Louis')->firstOrFail();
        $this->assertNotNull($etu->photo);
        $this->get("/fichiers/photo/{$etu->id}")->assertOk();
        $as->post('/paiements', ['etudiant_id' => $etu->id, 'montant' => 25000, 'tranche_numero' => 1, 'date_paiement' => today()->format('Y-m-d'), 'methode' => 'moncash', 'reference' => 'MC123'])->assertRedirect();
        $as->get('/paiements/'.\App\Models\Paiement::firstOrFail()->id.'/recu')->assertOk()->assertHeader('content-type', 'application/pdf');
        $as->put("/etudiants/{$etu->id}", ['nom' => 'Louis', 'prenom' => 'Kervens', 'phone' => '+509 3333 4444'])->assertRedirect();
        $this->assertSame('+509 3333 4444', $etu->fresh()->phone);

        // Professeur : première connexion, sujet, notes
        $prof->forceFill(['must_change_password' => false])->save();
        $this->actingAs($prof)->post('/prof/sujets', ['matiere_id' => $m->id, 'titre' => 'Sujet intra', 'examen_id' => $examen->id,
            'fichier' => UploadedFile::fake()->create('sujet.pdf', 50, 'application/pdf')])->assertRedirect();
        $sujet = SujetExamen::firstOrFail();
        $this->actingAs($prof)->get("/fichiers/sujet/{$sujet->id}")->assertOk();
        $this->actingAs($admin)->get("/fichiers/sujet/{$sujet->id}")->assertOk();
        $this->actingAs($etu->fresh()->forceFill(['must_change_password' => false]))->get("/fichiers/sujet/{$sujet->id}")->assertForbidden();

        $this->actingAs($prof)->post('/notes/saisie', ['examen_id' => $examen->id, 'matiere_id' => $m->id, 'notes' => [$etu->id => '81'], 'absent' => []])->assertRedirect();
        $this->actingAs($admin)->post("/examens/{$examen->id}/publication")->assertRedirect();
        $final = Examen::create(['annee_academique_id' => $examen->annee_academique_id, 'faculte_id' => $f->id, 'nom' => 'Final S1', 'type' => 'final', 'session' => 1, 'date_debut' => '2027-02-08', 'date_fin' => '2027-02-12']);
        $this->actingAs($admin)->post("/resultats/classe/decisions?classe={$classe->id}")->assertRedirect();
        $this->assertSame('inscrit', $etu->inscriptions()->first()->statut, 'Sans note finale, la décision reste en attente');
        $this->actingAs($prof)->post('/notes/saisie', ['examen_id' => $final->id, 'matiere_id' => $m->id, 'notes' => [$etu->id => '70']])->assertRedirect();
        $this->assertTrue($examen->fresh()->notes_publiees);

        // Délibération et décisions
        $this->actingAs($admin)->get("/resultats/classe?classe={$classe->id}")->assertOk()->assertSee('Louis');
        $this->actingAs($admin)->post("/resultats/classe/decisions?classe={$classe->id}")->assertRedirect();
        $this->assertSame('admis', $etu->inscriptions()->first()->statut);

        // Profil et mot de passe
        $this->actingAs($admin)->put('/profil', ['phone' => '+509 1111 2222', 'email' => 'admin@itac.ht'])->assertRedirect();
        $this->actingAs($admin)->post("/admin/utilisateurs/{$prof->id}/statut")->assertRedirect();
        $this->assertFalse($prof->fresh()->actif);
        $this->actingAs($admin)->post("/admin/utilisateurs/{$admin->id}/statut")->assertSessionHas('error');

        // Journal
        $this->actingAs($admin)->get('/admin/journal?action=paiement')->assertOk()->assertSee('REC-');
    }
}
