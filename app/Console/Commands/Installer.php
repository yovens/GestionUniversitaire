<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\AnneeAcademique;
use App\Models\User;
use Illuminate\Console\Command;

class Installer extends Command
{
    protected $signature = 'itac:installer
        {--username=admin : Identifiant de l\'administrateur}
        {--prenom=Administrateur} {--nom=ITAC}
        {--password= : Mot de passe (sinon demandé)}
        {--annee= : Année académique courante, ex. 2026-2027}';

    protected $description = 'Crée le premier administrateur et l\'année académique courante';

    public function handle(): int
    {
        $username = $this->option('username');
        if (User::where('username', $username)->exists()) {
            $this->error("L'identifiant « $username » existe déjà.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Mot de passe de l\'administrateur (8 caractères minimum)');
        if (! $password || strlen($password) < 8) {
            $this->error('Mot de passe trop court.');

            return self::FAILURE;
        }

        User::create([
            'role' => Role::Admin->value, 'username' => $username, 'prenom' => $this->option('prenom'), 'nom' => $this->option('nom'),
            'password' => $password, 'must_change_password' => false, 'actif' => true,
        ]);
        $this->info("Administrateur « $username » créé.");

        $libelle = $this->option('annee');
        if (! $libelle && ! AnneeAcademique::exists()) {
            $y = now()->month >= 8 ? now()->year : now()->year - 1;
            $libelle = $this->ask('Année académique courante', $y.'-'.($y + 1));
        }
        if ($libelle && preg_match('/^(\d{4})-(\d{4})$/', $libelle, $m)) {
            $annee = AnneeAcademique::firstOrCreate(['libelle' => $libelle], ['date_debut' => $m[1].'-10-01', 'date_fin' => $m[2].'-07-31']);
            $annee->definirCommeCourante();
            $this->info("Année courante : $libelle");
        }

        $this->line('Connectez-vous, puis créez les facultés, les classes, les cours et le personnel.');

        return self::SUCCESS;
    }
}
