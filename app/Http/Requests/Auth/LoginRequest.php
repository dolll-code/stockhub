<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(ActivityLogger $logger): void
    {
        $this->ensureIsNotRateLimited();

        $email = Str::lower(trim($this->string('email')));
        $password = (string) $this->input('password');

        $user = User::query()->where('email', $email)->first();

        // Generic error message for all failure modes (PRD §5.1 FR-AUTH-03, AC-AUTH-02)
        $genericErrorMessage = 'Email atau kata sandi yang Anda masukkan salah.';

        if (! $user || ! Hash::check($password, $user->password) || ! $user->is_active) {
            RateLimiter::hit($this->throttleKey());

            $logger->log(
                action: 'auth.login_failed',
                description: "Percobaan login gagal untuk email [{$email}]",
                subject: $user,
                actor: null,
                oldValues: ['email' => $email],
                newValues: null,
                ipAddress: $this->ip(),
                userAgent: $this->userAgent()
            );

            throw ValidationException::withMessages([
                'email' => $genericErrorMessage,
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Authenticate without remember-me (PRD A-08)
        Auth::login($user, remember: false);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $this->ip(),
        ])->save();

        $logger->log(
            action: 'auth.login',
            description: "Pengguna [{$user->name}] berhasil masuk",
            subject: $user,
            actor: $user,
            oldValues: null,
            newValues: null,
            ipAddress: $this->ip(),
            userAgent: $this->userAgent()
        );
    }

    /**
     * Ensure the login request is not rate limited (PRD §5.1 FR-AUTH-04, AC-AUTH-03).
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());
        $message = "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.";

        if ($this->expectsJson()) {
            abort(response()->json([
                'message' => $message,
                'code' => 'TOO_MANY_REQUESTS',
                'retry_after' => $seconds,
            ], 429, ['Retry-After' => (string) $seconds]));
        }

        abort(response()->view('errors.429', [
            'seconds' => $seconds,
            'message' => $message,
        ], 429, ['Retry-After' => (string) $seconds]));
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
