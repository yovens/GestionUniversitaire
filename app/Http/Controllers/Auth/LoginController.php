<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $cle = Str::lower($data['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($cle, 5)) {
            $sec = RateLimiter::availableIn($cle);
            throw ValidationException::withMessages([
                'username' => "Trop de tentatives. Réessayez dans {$sec} secondes.",
            ]);
        }

        // Connexion par nom d'utilisateur, e-mail ou matricule
        $login = trim($data['username']);
        $user = User::where('username', $login)->orWhere('email', $login)->orWhere('matricule', $login)->first();

        if (! $user || ! Auth::getProvider()->validateCredentials($user, ['password' => $data['password']])) {
            RateLimiter::hit($cle, 60);
            throw ValidationException::withMessages(['username' => 'Identifiant ou mot de passe incorrect.']);
        }

        if (! $user->actif) {
            throw ValidationException::withMessages(['username' => 'Ce compte est désactivé. Contactez l’administration.']);
        }

        RateLimiter::clear($cle);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        Journal::log('connexion', "Connexion de {$user->nom_complet}", $user);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Vous êtes déconnecté.');
    }
}
