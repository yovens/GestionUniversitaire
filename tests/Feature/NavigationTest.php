<?php

namespace Tests\Feature;

use App\Models\Affectation;
use App\Models\Examen;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ouvre toutes les pages de chaque rôle : aucune ne doit planter. */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public static function roles(): array
    {
        return [['admin'], ['secretariat'], ['doyen.info']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('roles')]
    public function test_pages_du_personnel(string $username): void
    {
        $user = User::where('username', $username)->firstOrFail();
        $etu = User::where('role', 'etudiant')->where('faculte_id', $user->faculte_id ?? 1)->first();
        $insc = Inscription::where('etudiant_id', $etu->id)->first();
        $examen = Examen::first();
        $matiere = Inscription::find($insc->id)->classe->matieres()->where('session', 1)->first();
        $paiement = Paiement::where('etudiant_id', $etu->id)->first() ?? Paiement::first();

        $urls = [
            '/tableau-de-bord', '/profil', '/mot-de-passe', '/calendrier', '/calendrier?mois=2027-02',
            '/etudiants', '/etudiants?statut=non_inscrit', '/etudiants?q=Louis', '/etudiants/create', "/etudiants/{$etu->id}", "/etudiants/{$etu->id}/edit",
            '/paiements', '/paiements/nouveau', '/paiements/nouveau?q=a', "/paiements/nouveau?etudiant={$etu->id}", '/paiements/export',
            '/frais', '/examens', '/examens/create',
            '/notes/saisie', "/notes/saisie?classe={$insc->classe_id}", "/notes/saisie?examen={$examen->id}&matiere={$matiere->id}",
            '/resultats', "/resultats/classe?classe={$insc->classe_id}", "/resultats/classe?classe={$insc->classe_id}&session=1",
            "/resultats/classe/pdf?classe={$insc->classe_id}", "/bulletins/{$insc->id}",
        ];
        // Un examen commun à toutes les facultés n'est modifiable que par l'administration centrale
        $urls[] = $user->faculteScope() === null ? "/examens/{$examen->id}/edit" : '/examens';
        if ($paiement) {
            $urls[] = "/paiements/{$paiement->id}/recu";
        }
        if ($user->hasRole('admin', 'doyen')) {
            $urls = array_merge($urls, ['/classes', '/classes/create', '/matieres', '/matieres/create', "/matieres/{$matiere->id}/edit", '/affectations', '/affectations?sans_prof=1']);
        }
        if ($user->isAdmin()) {
            $urls = array_merge($urls, ['/admin/facultes', '/admin/facultes/create', '/admin/facultes/1/edit', '/admin/annees', '/admin/annees/create',
                '/admin/utilisateurs', '/admin/utilisateurs/create', '/admin/utilisateurs?role=professeur', '/admin/journal']);
        }

        foreach ($urls as $url) {
            $r = $this->actingAs($user)->get($url);
            $this->assertSame(200, $r->getStatusCode(), "$username → $url a répondu {$r->getStatusCode()}");
        }
        if ($user->faculteScope() !== null) {
            $this->actingAs($user)->get("/examens/{$examen->id}/edit")->assertForbidden();
        }
    }

    public function test_pages_professeur(): void
    {
        $prof = User::where('username', 'prof1')->firstOrFail();
        $aff = Affectation::where('professeur_id', $prof->id)->with('matiere')->first();
        $examen = Examen::where('session', $aff->matiere->session)->first();
        foreach (['/tableau-de-bord', '/prof/cours', "/prof/cours/{$aff->id}", '/prof/sujets', '/notes/saisie', '/calendrier', '/profil',
            "/notes/saisie?examen={$examen->id}&matiere={$aff->matiere_id}"] as $url) {
            $this->actingAs($prof)->get($url)->assertOk();
        }
        foreach (['/etudiants', '/paiements', '/admin/facultes', '/classes', '/examens', '/etudiant/notes'] as $interdit) {
            $this->actingAs($prof)->get($interdit)->assertForbidden();
        }
    }

    public function test_pages_etudiant(): void
    {
        $etu = User::where('role', 'etudiant')->firstOrFail();
        foreach (['/tableau-de-bord', '/etudiant/notes', '/etudiant/paiements', '/calendrier', '/profil', '/etudiant/bulletin.pdf'] as $url) {
            $this->actingAs($etu)->get($url)->assertOk();
        }
        $p = Paiement::where('etudiant_id', $etu->id)->first();
        if ($p) {
            $this->actingAs($etu)->get("/paiements/{$p->id}/recu")->assertOk();
        }
        $autre = Paiement::where('etudiant_id', '!=', $etu->id)->first();
        $this->actingAs($etu)->get("/paiements/{$autre->id}/recu")->assertForbidden();
        foreach (['/etudiants', '/notes/saisie', '/admin/journal', '/prof/cours', '/frais'] as $interdit) {
            $this->actingAs($etu)->get($interdit)->assertForbidden();
        }
    }

    public function test_invite_redirige_vers_connexion(): void
    {
        $this->get('/tableau-de-bord')->assertRedirect('/connexion');
        $this->get('/connexion')->assertOk()->assertSee('Se connecter');
        $this->get('/')->assertRedirect('/connexion');
    }
}
