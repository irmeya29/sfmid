<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('roles');
        $activities = ActivityLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(8)
            ->get();

        return view('profile.edit', compact('user', 'activities'));
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        $oldValues = $user->only(['name', 'email', 'phone']);

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $logger->log('updated_profile', 'profile', "Profil {$user->email} modifié.", $user, $oldValues, $user->fresh()->only(['name', 'email', 'phone']));

        return back()->with('success', 'Profil mis à jour.');
    }
}
