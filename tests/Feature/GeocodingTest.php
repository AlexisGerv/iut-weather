<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.openweather.key' => 'test-key',
        'services.openweather.base_url' => 'https://api.openweathermap.org',
    ]);
});

it('returns the coordinates of a city from the OpenWeather geocoding API', function () {
    Http::fake([
        'api.openweathermap.org/geo/1.0/direct*' => Http::response([
            [
                'name' => 'Dijon',
                'local_names' => ['fr' => 'Dijon'],
                'lat' => 47.3215806,
                'lon' => 5.0414701,
                'country' => 'FR',
                'state' => 'Bourgogne-Franche-Comté',
            ],
        ]),
    ]);

    $this->getJson('/api/geocoding?city=Dijon&country=FR')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Dijon')
        ->assertJsonPath('data.0.country', 'FR')
        ->assertJsonPath('data.0.state', 'Bourgogne-Franche-Comté')
        ->assertJsonPath('data.0.latitude', 47.3215806)
        ->assertJsonPath('data.0.longitude', 5.0414701);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/geo/1.0/direct')
        && $request['q'] === 'Dijon,FR'
        && $request['appid'] === 'test-key');
});

it('returns an empty list when no city matches', function () {
    Http::fake([
        'api.openweathermap.org/geo/1.0/direct*' => Http::response([]),
    ]);

    $this->getJson('/api/geocoding?city=Nowhereville')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('requires a city name', function () {
    Http::fake();

    $this->getJson('/api/geocoding')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('city');

    Http::assertNothingSent();
});

it('returns 503 when the geocoding API fails', function () {
    Http::fake([
        'api.openweathermap.org/*' => Http::response(status: 500),
    ]);

    $this->getJson('/api/geocoding?city=Dijon')
        ->assertStatus(503)
        ->assertJsonPath('message', 'Le service météo est indisponible.');
});
