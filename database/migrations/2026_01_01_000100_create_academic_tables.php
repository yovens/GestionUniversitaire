<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annees_academiques', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 20)->unique(); // 2025-2026
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('courante')->default(false)->index();
            $table->timestamps();
        });

        // Une "classe" = un niveau d'étude dans une faculté (ex : L1 Informatique)
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculte_id')->constrained('facultes')->restrictOnDelete();
            $table->string('nom', 100);
            $table->unsignedTinyInteger('niveau'); // 1..6
            $table->timestamps();
            $table->unique(['faculte_id', 'nom']);
        });

        Schema::create('matieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculte_id')->constrained('facultes')->restrictOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('nom', 150);
            $table->unsignedTinyInteger('coefficient')->default(1);
            $table->unsignedTinyInteger('session')->default(1); // 1 ou 2
            $table->timestamps();
        });

        // Qui enseigne quel cours pendant quelle année académique
        Schema::create('affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professeur_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['matiere_id', 'annee_academique_id']);
        });

        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->restrictOnDelete();
            $table->string('statut', 20)->default('inscrit'); // inscrit, admis, reprise, abandon
            $table->date('date_inscription');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['etudiant_id', 'annee_academique_id']);
        });

        // Périodes d'examens planifiées par le secrétariat
        Schema::create('examens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->restrictOnDelete();
            $table->foreignId('faculte_id')->nullable()->constrained('facultes')->cascadeOnDelete(); // null = toutes
            $table->string('nom', 150);
            $table->string('type', 20); // intra, final, reprise
            $table->unsignedTinyInteger('session')->default(1);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedTinyInteger('tranche_requise')->nullable(); // versement exigé pour voir les notes
            $table->boolean('notes_publiees')->default(false);
            $table->timestamp('publie_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examens')->restrictOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->restrictOnDelete();
            $table->foreignId('etudiant_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('valeur', 5, 2)->nullable(); // null = absent
            $table->boolean('absent')->default(false);
            $table->foreignId('saisie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['examen_id', 'matiere_id', 'etudiant_id']);
        });

        Schema::create('sujets_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professeur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('examen_id')->nullable()->constrained('examens')->nullOnDelete();
            $table->string('titre', 150);
            $table->text('consigne')->nullable();
            $table->string('fichier')->nullable();
            $table->string('fichier_nom')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['sujets_examen', 'notes', 'examens', 'inscriptions', 'affectations', 'matieres', 'classes', 'annees_academiques'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
