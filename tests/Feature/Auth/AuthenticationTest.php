<?php

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('TC-AUTH-01: successful login redirects to dashboard, updates login metadata, and logs activity (AC-AUTH-01)', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('Password123!'),
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull();
    expect($user->last_login_ip)->not->toBeNull();

    // Verify activity log
    $log = ActivityLog::where('action', 'auth.login')->where('user_id', $user->id)->first();
    expect($log)->not->toBeNull();
    expect($log->description)->toContain($user->name);
});

test('TC-AUTH-02: login failure returns identical generic message for unreg, wrong pass, and inactive user (AC-AUTH-02)', function () {
    $activeUser = User::factory()->create([
        'email' => 'active@example.com',
        'password' => Hash::make('Password123!'),
        'is_active' => true,
    ]);

    $inactiveUser = User::factory()->create([
        'email' => 'inactive@example.com',
        'password' => Hash::make('Password123!'),
        'is_active' => false,
    ]);

    // 1. Unregistered email
    $resUnreg = $this->post('/login', [
        'email' => 'notfound@example.com',
        'password' => 'Password123!',
    ]);
    $resUnreg->assertSessionHasErrors('email');
    $msgUnreg = session('errors')->first('email');

    // 2. Wrong password
    $resWrongPass = $this->post('/login', [
        'email' => 'active@example.com',
        'password' => 'WrongPassword123!',
    ]);
    $resWrongPass->assertSessionHasErrors('email');
    $msgWrongPass = session('errors')->first('email');

    // 3. Inactive account with correct password
    $resInactive = $this->post('/login', [
        'email' => 'inactive@example.com',
        'password' => 'Password123!',
    ]);
    $resInactive->assertSessionHasErrors('email');
    $msgInactive = session('errors')->first('email');

    // Messages must be identical
    expect($msgUnreg)->toBe('Email atau kata sandi yang Anda masukkan salah.');
    expect($msgWrongPass)->toBe($msgUnreg);
    expect($msgInactive)->toBe($msgUnreg);

    // Verify activity logs recorded auth.login_failed without passwords
    $logs = ActivityLog::where('action', 'auth.login_failed')->get();
    expect($logs->count())->toBeGreaterThanOrEqual(3);
    foreach ($logs as $log) {
        expect($log->old_values)->not->toHaveKey('password');
        expect(json_encode($log))->not->toContain('Password123!');
    }
});

test('TC-AUTH-03: login throttled after 5 failed attempts returning HTTP 429 on 6th attempt (AC-AUTH-03)', function () {
    RateLimiter::clear(sha1('throttle@example.com|127.0.0.1'));

    for ($i = 1; $i <= 5; $i++) {
        $response = $this->post('/login', [
            'email' => 'throttle@example.com',
            'password' => 'WrongPass123!',
        ]);
        $response->assertSessionHasErrors('email');
    }

    // 6th attempt must return HTTP 429
    $response6 = $this->post('/login', [
        'email' => 'throttle@example.com',
        'password' => 'WrongPass123!',
    ]);

    $response6->assertStatus(429);
    $response6->assertHeader('Retry-After');
});

test('TC-AUTH-04: user with must_change_password can only access change password flow (AC-AUTH-04)', function () {
    $user = User::factory()->create([
        'must_change_password' => true,
    ]);

    // Accessing dashboard should redirect to /password/change
    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertRedirect(route('password.change'));

    // Can access /password/change
    $renderResponse = $this->actingAs($user)->get('/password/change');
    $renderResponse->assertStatus(200);

    // Submit new password matching policy
    $submitResponse = $this->actingAs($user)->put('/password/change', [
        'password' => 'BrandNewPassword123!',
        'password_confirmation' => 'BrandNewPassword123!',
    ]);

    $submitResponse->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->must_change_password)->toBeFalse();
    expect(Hash::check('BrandNewPassword123!', $user->password))->toBeTrue();

    // Verify activity log recorded auth.password_changed
    $log = ActivityLog::where('action', 'auth.password_changed')->where('user_id', $user->id)->first();
    expect($log)->not->toBeNull();
});

test('TC-AUTH-05: active user deactivated by admin has session terminated on next request (AC-AUTH-05)', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user);

    // User is active, can access profile
    $this->get('/profile')->assertStatus(200);

    // Admin deactivates user in database
    $user->update(['is_active' => false]);

    // Next request is rejected and user is logged out
    $response = $this->get('/profile');
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('users can logout destroying session and logging activity (FR-AUTH-06)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect(route('login'));

    $log = ActivityLog::where('action', 'auth.logout')->where('user_id', $user->id)->first();
    expect($log)->not->toBeNull();
});
