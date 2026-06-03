<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticateUserAction
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    /**
     * @throws ValidationException
     */
    public function execute(string $email, string $password, bool $remember, string $ipAddress): User
    {
        $email = Str::lower(trim($email));
        $key = $this->throttleKey($email, $ipAddress);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => 'Connexion momentanément bloquée. Réessayez dans quelques instants.',
            ]);
        }

        $authenticated = Auth::attempt([
            'email' => $email,
            'password' => $password,
            'is_active' => true,
        ], $remember);

        if (! $authenticated) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Les identifiants fournis sont incorrects.',
            ]);
        }

        RateLimiter::clear($key);

        /** @var User $user */
        $user = Auth::user();

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return $user;
    }

    private function throttleKey(string $email, string $ipAddress): string
    {
        return Str::transliterate(Str::lower($email).'|'.$ipAddress);
    }
}
