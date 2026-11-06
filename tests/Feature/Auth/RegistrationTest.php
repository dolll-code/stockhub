<?php

test('public registration route is not available (404)', function () {
    $response = $this->get('/register');

    $response->assertStatus(404);
});

test('public registration post is not available (404)', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(404);
});
