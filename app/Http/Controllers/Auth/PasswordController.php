<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.password', ['force' => $this->user()->must_change_password]);
    }

    public function update(Request $request)
    {
        $user = $this->user();
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l’actuel.',
        ]);

        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'must_change_password' => false,
        ])->save();
        $request->session()->regenerate();
        Journal::log('mot_de_passe', 'Changement de mot de passe', $user);

        return redirect()->route('dashboard')->with('success', 'Mot de passe mis à jour.');
    }
}
