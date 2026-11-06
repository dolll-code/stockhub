<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $oldValues = [
            'name' => $user?->name,
            'email' => $user?->email,
        ];

        $user?->fill($request->validated());

        if ($user?->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user?->save();

        if ($user) {
            $logger->log(
                action: 'user.updated',
                description: "Pengguna [{$user->name}] memperbarui data profil sendiri",
                subject: $user,
                actor: $user,
                oldValues: $oldValues,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );
        }

        return Redirect::route('profile.edit')->with('status', 'Profil berhasil diperbarui.');
    }
}
