<?php

use App\Models\User;

beforeEach(function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'default']);
});

test('new users can register via api', function () {
    $payload = [
        'name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    $response = $this->postJson('/api/register', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'satus' => 'success',
            'message' => 'User registered successfully',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'last_name' => 'User',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    $this->assertAuthenticatedAs($user);
});

test('registration fails when last_name is missing', function () {
    $payload = [
        'name' => 'Test',
        'email' => 'invalid@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    $response = $this->postJson('/api/register', $payload);

    $response->assertStatus(422);
});
