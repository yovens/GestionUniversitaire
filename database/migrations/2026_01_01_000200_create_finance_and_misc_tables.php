<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Échéancier : montant de chaque versement par faculté et par année
        Schema::create('tranches_frais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculte_id')->constrained('facultes')->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->string('libelle', 100);
            $table->decimal('montant', 12, 2);
            $table->date('date_limite')->nullable();
            $table->timestamps();
            $table->unique(['faculte_id', 'annee_academique_id', 'numero']);
        });

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->string('numero_recu', 30)->unique();
            $table->foreignId('etudiant_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->restrictOnDelete();
            $table->unsignedTinyInteger('tranche_numero')->nullable();
            $table->decimal('montant', 12, 2);
            $table->date('date_paiement');
            $table->string('methode', 30);
            $table->string('reference', 100)->nullable();
            $table->string('note')->nullable();
            $table->string('justificatif')->nullable();
            $table->foreignId('enregistre_par')->nullable()->constrained('users')->nullOnDelete();
            // Un paiement n'est jamais supprimé : il est annulé (traçabilité comptable)
            $table->timestamp('annule_at')->nullable();
            $table->foreignId('annule_par')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motif_annulation')->nullable();
            $table->timestamps();
            $table->index(['etudiant_id', 'annee_academique_id']);
        });

        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculte_id')->nullable()->constrained('facultes')->cascadeOnDelete();
            $table->foreignId('examen_id')->nullable()->constrained('examens')->cascadeOnDelete();
            $table->string('titre', 200);
            $table->text('description')->nullable();
            $table->string('type', 20); // cours, examen, reprise, conge, autre
            $table->date('date_debut');
            $table->date('date_fin');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('date_debut');
        });

        Schema::create('journal_activites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('sujet_type', 60)->nullable();
            $table->unsignedBigInteger('sujet_id')->nullable();
            $table->text('description');
            $table->json('details')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['sujet_type', 'sujet_id']);
        });
    }

    public function down(): void
    {
        foreach (['journal_activites', 'evenements', 'paiements', 'tranches_frais'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
