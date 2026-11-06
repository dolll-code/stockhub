<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\StockHubPassword;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    /**
     * Show the mandatory password change form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user && ! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password');
    }

    /**
     * Process the mandatory password change.
     */
    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', new StockHubPassword($user?->email)],
        ]);

        $user?->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        if ($user) {
            $logger->log(
                action: 'auth.password_changed',
                description: "Pengguna [{$user->name}] berhasil mengganti kata sandi sementara (FR-AUTH-05)",
                subject: $user,
                actor: $user,
                oldValues: null,
                newValues: null,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );
        }

        return redirect()->route('dashboard')->with('success', 'Kata sandi Anda berhasil diperbarui. Selamat datang di StockHub!');
    }
}
