<?php

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('TC-LOG-02: ActivityLogger strips sensitive fields and secrets from payloads (FR-LOG-03, SEC-16)', function () {
    $user = User::factory()->create();
    $logger = app(ActivityLogger::class);

    $log = $logger->log(
        action: 'user.created',
        description: 'Testing sensitive filter',
        subject: $user,
        actor: $user,
        oldValues: [
            'name' => 'Old Name',
            'password' => 'SecretPass123!',
            'remember_token' => 'token123456',
            'api_key' => 'secret_api_key_xyz',
        ],
        newValues: [
            'name' => 'New Name',
            'password' => 'NewSecretPass123!',
            'app_key' => 'base64:randomkey',
            'nested' => [
                'field' => 'allowed_value',
                'password' => 'nested_secret',
            ],
        ],
        ipAddress: '192.168.1.100',
        userAgent: 'Security-Audit-Agent'
    );

    expect($log->old_values)->toHaveKey('name');
    expect($log->old_values)->not->toHaveKey('password');
    expect($log->old_values)->not->toHaveKey('remember_token');
    expect($log->old_values)->not->toHaveKey('api_key');

    expect($log->new_values)->toHaveKey('name');
    expect($log->new_values)->not->toHaveKey('password');
    expect($log->new_values)->not->toHaveKey('app_key');
    expect($log->new_values['nested'])->toHaveKey('field');
    expect($log->new_values['nested'])->not->toHaveKey('password');

    // Verify raw JSON in database contains no secrets
    $rawLog = json_encode($log->fresh());
    expect($rawLog)->not->toContain('SecretPass123!');
    expect($rawLog)->not->toContain('NewSecretPass123!');
    expect($rawLog)->not->toContain('secret_api_key_xyz');
    expect($rawLog)->not->toContain('base64:randomkey');
});
