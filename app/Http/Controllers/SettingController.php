<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingUpdateRequest;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Show system settings form (FR-SET-01..02).
     * Restricted strictly to Super Admin.
     */
    public function edit(): View
    {
        Gate::authorize('settings.manage');

        $settings = [
            'company_name' => Setting::getValue('company_name', 'StockHub Inventory System'),
            'company_address' => Setting::getValue('company_address', ''),
            'company_phone' => Setting::getValue('company_phone', ''),
            'max_backdate_days' => (int) Setting::getValue('max_backdate_days', '7'),
            'company_logo_path' => Setting::getValue('company_logo_path'),
        ];

        return view('settings.edit', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update system settings and record audit log.
     */
    public function update(SettingUpdateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('settings.manage');

        $validated = $request->validated();
        $user = $request->user();

        $oldValues = [
            'company_name' => Setting::getValue('company_name'),
            'company_address' => Setting::getValue('company_address'),
            'company_phone' => Setting::getValue('company_phone'),
            'max_backdate_days' => (int) Setting::getValue('max_backdate_days', '7'),
            'company_logo_path' => Setting::getValue('company_logo_path'),
        ];

        // Handle company logo upload / removal
        $currentLogo = Setting::getValue('company_logo_path');
        $newLogoPath = $currentLogo;

        if ($request->boolean('remove_logo') && $currentLogo) {
            if (Storage::disk('public')->exists($currentLogo)) {
                Storage::disk('public')->delete($currentLogo);
            }
            $newLogoPath = null;
        } elseif ($request->hasFile('company_logo')) {
            $file = $request->file('company_logo');
            if ($file && $file->isValid()) {
                if ($currentLogo && Storage::disk('public')->exists($currentLogo)) {
                    Storage::disk('public')->delete($currentLogo);
                }
                $ext = $file->getClientOriginalExtension() ?: 'png';
                $filename = 'logo_'.Str::random(20).'.'.$ext;
                $newLogoPath = $file->storeAs('settings', $filename, 'public');
            }
        }

        Setting::setValue('company_name', $validated['company_name'], $user?->id);
        Setting::setValue('company_address', $validated['company_address'] ?? null, $user?->id);
        Setting::setValue('company_phone', $validated['company_phone'] ?? null, $user?->id);
        Setting::setValue('max_backdate_days', (string) $validated['max_backdate_days'], $user?->id);
        Setting::setValue('company_logo_path', $newLogoPath, $user?->id);

        $newValues = [
            'company_name' => $validated['company_name'],
            'company_address' => $validated['company_address'] ?? null,
            'company_phone' => $validated['company_phone'] ?? null,
            'max_backdate_days' => $validated['max_backdate_days'],
            'company_logo_path' => $newLogoPath,
        ];

        $logger->log(
            action: 'setting.updated',
            description: "Pengguna [{$user?->name}] memperbarui konfigurasi pengaturan sistem",
            subject: null,
            actor: $user,
            oldValues: $oldValues,
            newValues: $newValues,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('settings.edit')
            ->with('success', 'Pengaturan sistem berhasil diperbarui.');
    }
}
