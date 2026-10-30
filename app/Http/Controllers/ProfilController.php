<?php

namespace App\Http\Controllers;

use App\Services\Fichiers;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfilController extends Controller
{
    public function show()
    {
        return view('profil.show', ['user' => $this->user()->load('faculte')]);
    }

    public function update(Request $request)
    {
        $user = $this->user();
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'adresse' => ['nullable', 'string', 'max:255'],
            'contact_urgence' => ['nullable', 'string', 'max:150'],
        ]);
        $user->update($data);
        Journal::log('profil', 'Mise à jour du profil', $user);

        return back()->with('success', 'Profil mis à jour.');
    }

    public function photo(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('itac.uploads.photo_max_kb'), 'dimensions:max_width=5000,max_height=5000'],
        ]);
        $user = $this->user();
        $ancien = $user->photo;
        $user->update(['photo' => Fichiers::stocker($request->file('photo'), 'photos')]);
        Fichiers::supprimer($ancien);

        return back()->with('success', 'Photo mise à jour.');
    }
}
