<?php

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->adminGudang = User::factory()->create();
    $this->adminGudang->assignRole('admin_gudang');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('staff_gudang');

    $this->manajer = User::factory()->create();
    $this->manajer->assignRole('manajer');
});

test('TC-USR-01: duplicate email case-insensitive is rejected when creating user (AC-USR-01)', function () {
    User::factory()->create(['email' => 'staff@stockhub.local']);

    $response = $this->actingAs($this->superAdmin)->post('/users', [
        'name' => 'Staff Duplicate',
        'email' => 'STAFF@STOCKHUB.LOCAL',
        'role' => 'staff_gudang',
        'password' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('email');
    expect(User::where('name', 'Staff Duplicate')->exists())->toBeFalse();
});

test('TC-USR-02: last active super admin cannot be deactivated or demoted (AC-USR-02, BR-24)', function () {
    // There is only 1 super admin ($this->superAdmin). Attempt to deactivate:
    $responseDeactivate = $this->actingAs($this->superAdmin)->patch("/users/{$this->superAdmin->id}/status");
    $responseDeactivate->assertSessionHasErrors('status');
    expect($this->superAdmin->fresh()->is_active)->toBeTrue();

    // Another admin attempts to demote this last active SA:
    $sa2 = User::factory()->create();
    $sa2->assignRole('super_admin');

    // Demote $this->superAdmin while $sa2 exists -> should succeed
    $responseDemote = $this->actingAs($sa2)->put("/users/{$this->superAdmin->id}", [
        'name' => $this->superAdmin->name,
        'email' => $this->superAdmin->email,
        'role' => 'admin_gudang',
    ]);
    $responseDemote->assertSessionHasNoErrors();
    expect($this->superAdmin->fresh()->hasRole('admin_gudang'))->toBeTrue();

    // Now $sa2 is the last active super admin. Attempt to demote $sa2:
    $sa2AsOther = User::factory()->create();
    $sa2AsOther->assignRole('super_admin');
    $sa2AsOther->update(['is_active' => false]); // Inactive SA doesn't count

    $responseDemoteLast = $this->actingAs($sa2)->put("/users/{$sa2->id}", [
        'name' => $sa2->name,
        'email' => $sa2->email,
        'role' => 'manajer',
    ]);
    $responseDemoteLast->assertSessionHasErrors('role');
    expect($sa2->fresh()->hasRole('super_admin'))->toBeTrue();
});

test('user cannot deactivate their own account (BR-24)', function () {
    // Create second active super admin so last SA rule is not triggered
    $sa2 = User::factory()->create();
    $sa2->assignRole('super_admin');

    $response = $this->actingAs($this->superAdmin)->patch("/users/{$this->superAdmin->id}/status");
    $response->assertSessionHasErrors('status');
    expect($this->superAdmin->fresh()->is_active)->toBeTrue();
});

test('user cannot change their own role (BR-24)', function () {
    $response = $this->actingAs($this->superAdmin)->put("/users/{$this->superAdmin->id}", [
        'name' => $this->superAdmin->name,
        'email' => $this->superAdmin->email,
        'role' => 'manajer',
    ]);

    $response->assertSessionHasErrors('role');
    expect($this->superAdmin->fresh()->hasRole('super_admin'))->toBeTrue();
});

test('TC-USR-03: reset password sets new temporary password and invalidates user sessions (AC-USR-03, FR-USR-05)', function () {
    $targetUser = User::factory()->create([
        'email' => 'target@example.com',
        'password' => Hash::make('OldPassword123!'),
        'must_change_password' => false,
    ]);

    // Simulate an active session in the database
    DB::table('sessions')->insert([
        'id' => 'session_target_user_123',
        'user_id' => $targetUser->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest Test Browser',
        'payload' => 'dummy_payload',
        'last_activity' => time(),
    ]);

    expect(DB::table('sessions')->where('user_id', $targetUser->id)->count())->toBe(1);

    $response = $this->actingAs($this->superAdmin)->post("/users/{$targetUser->id}/reset-password", [
        'password' => 'TempPassword456!',
    ]);

    $response->assertSessionHasNoErrors();

    $targetUser->refresh();
    expect($targetUser->must_change_password)->toBeTrue();
    expect(Hash::check('TempPassword456!', $targetUser->password))->toBeTrue();

    // Session must be purged
    expect(DB::table('sessions')->where('user_id', $targetUser->id)->count())->toBe(0);

    // Verify activity log recorded user.password_reset
    $log = ActivityLog::where('action', 'user.password_reset')->where('subject_id', $targetUser->id)->first();
    expect($log)->not->toBeNull();
});

test('TC-AUTHZ-01 & 05: non super admin roles cannot access user management (AC-USR-04)', function () {
    // Admin Gudang cannot access /users
    $this->actingAs($this->adminGudang)->get('/users')->assertStatus(403);
    $this->actingAs($this->adminGudang)->get('/users/create')->assertStatus(403);
    $this->actingAs($this->adminGudang)->get('/roles')->assertStatus(403);

    // Staff Gudang cannot access /users
    $this->actingAs($this->staffGudang)->get('/users')->assertStatus(403);
    $this->actingAs($this->staffGudang)->get('/roles')->assertStatus(403);

    // Manajer cannot access /users
    $this->actingAs($this->manajer)->get('/users')->assertStatus(403);
    $this->actingAs($this->manajer)->get('/roles')->assertStatus(403);
});

test('super admin can view user list, create, and update users', function () {
    // 1. List users with search & filter
    $listResponse = $this->actingAs($this->superAdmin)->get('/users?q='.$this->staffGudang->name);
    $listResponse->assertStatus(200);
    $listResponse->assertSee($this->staffGudang->name);

    // 2. Create user
    $createResponse = $this->actingAs($this->superAdmin)->post('/users', [
        'name' => 'Staff Baru',
        'email' => 'staffbaru@stockhub.local',
        'role' => 'staff_gudang',
        'password' => 'TemporaryPass123!',
    ]);
    $createResponse->assertRedirect(route('users.index'));

    $newUser = User::where('email', 'staffbaru@stockhub.local')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->must_change_password)->toBeTrue();
    expect($newUser->hasRole('staff_gudang'))->toBeTrue();

    // 3. Update user
    $updateResponse = $this->actingAs($this->superAdmin)->put("/users/{$newUser->id}", [
        'name' => 'Staff Baru Diedit',
        'email' => 'staffbaru@stockhub.local',
        'role' => 'admin_gudang',
    ]);
    $updateResponse->assertRedirect(route('users.index'));

    $newUser->refresh();
    expect($newUser->name)->toBe('Staff Baru Diedit');
    expect($newUser->hasRole('admin_gudang'))->toBeTrue();
    expect($newUser->hasRole('staff_gudang'))->toBeFalse(); // exactly one role maintained
});

test('super admin can view read-only role permission matrix (FR-USR-06)', function () {
    $response = $this->actingAs($this->superAdmin)->get('/roles');
    $response->assertStatus(200);
    $response->assertSee('dashboard.view');
    $response->assertSee('users.manage');
});
