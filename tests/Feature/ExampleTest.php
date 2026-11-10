<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('returns a successful response', function () {
    Http::fake(['api.openweathermap.org/*' => Http::response(status: 500)]);

    $response = $this->get('/');

    $response->assertStatus(200);
});

it('links guests to the login and register pages', function () {
    Http::fake(['api.openweathermap.org/*' => Http::response(status: 500)]);

    $this->get('/')
        ->assertOk()
        ->assertSee(route('login'))
        ->assertSee(route('register'));
});

it('shows the profile and logout links to authenticated users', function () {
    Http::fake(['api.openweathermap.org/*' => Http::response(status: 500)]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSeeText($user->name)
        ->assertSee(route('profile.edit'))
        ->assertSee(route('logout'))
        ->assertDontSee(route('register'));
});
