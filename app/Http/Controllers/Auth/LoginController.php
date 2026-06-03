<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthenticateUserAction $authenticateUserAction): RedirectResponse
    {
        $authenticateUserAction->execute(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            remember: $request->boolean('remember'),
            ipAddress: $request->ip() ?? 'unknown',
        );

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
