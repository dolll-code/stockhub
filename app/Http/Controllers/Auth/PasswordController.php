<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\StockHubPassword;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password from their own profile.
     *
     * Per PRD §5.1 FR-AUTH-09 & AC-AUTH-06:
     * Requires old password, validates password policy, terminates other device sessions.
     */
    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', new StockHubPassword($user?->email)],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini yang Anda masukkan salah.',
        ]);

        $user?->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        // Disconnect other device sessions per PRD §5.1 FR-AUTH-09
        Auth::logoutOtherDevices($validated['password']);

        if ($user) {
            $logger->log(
                action: 'auth.password_changed',
                description: "Pengguna [{$user->name}] mengganti kata sandi sendiri",
                subject: $user,
                actor: $user,
                oldValues: null,
                newValues: null,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );
        }

        return back()->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
