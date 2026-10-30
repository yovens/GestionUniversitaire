<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\Faculte;
use App\Models\User;
use Illuminate\Support\Collection;

abstract class Controller
{
    protected function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    /** Refuse l'accès si la ressource appartient à une autre faculté que celle de l'utilisateur. */
    protected function verifierFaculte(?int $faculteId): void
    {
        if (! $this->user()->peutAccederFaculte($faculteId)) {
            abort(403, 'Cette ressource appartient à une autre faculté.');
        }
    }

    protected function anneeCourante(): ?AnneeAcademique
    {
        return AnneeAcademique::courante();
    }

    /** Année choisie dans les filtres, sinon l'année courante. */
    protected function anneeChoisie(): ?AnneeAcademique
    {
        $id = request()->integer('annee');

        return $id ? AnneeAcademique::find($id) ?? $this->anneeCourante() : $this->anneeCourante();
    }

    protected function facultesVisibles(): Collection
    {
        return Faculte::visiblePar($this->user())->orderBy('nom')->get();
    }

    /** Faculté choisie dans le filtre (limitée au périmètre de l'utilisateur). */
    protected function faculteFiltre(): ?int
    {
        $scope = $this->user()->faculteScope();
        if ($scope !== null) {
            return $scope;
        }

        return request()->integer('faculte') ?: null;
    }

    protected function requireAnnee(): AnneeAcademique
    {
        $a = $this->anneeCourante();
        if (! $a) {
            abort(redirect()->route(auth()->user()->isAdmin() ? 'admin.annees.index' : 'dashboard')
                ->with('error', "Aucune année académique courante n'est définie."));
        }

        return $a;
    }
}
