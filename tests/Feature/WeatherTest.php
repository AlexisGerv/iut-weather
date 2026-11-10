<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.openweather.key' => 'test-key',
        'services.openweather.base_url' => 'https://api.openweathermap.org',
    ]);
});

it('returns the current weather of Dijon', function () {
    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'name' => 'Dijon',
            'dt' => 1762768800,
            'sys' => ['country' => 'FR'],
            'weather' => [['description' => 'ciel dégagé', 'icon' => '01d']],
            'main' => [
                'temp' => 12.5,
                'feels_like' => 11.2,
                'temp_min' => 10.0,
                'temp_max' => 14.0,
                'humidity' => 70,
                'pressure' => 1015,
            ],
            'wind' => ['speed' => 3.6],
        ]),
    ]);

    $this->getJson('/api/weather/dijon')
        ->assertOk()
        ->assertJsonPath('data.city', 'Dijon')
        ->assertJsonPath('data.description', 'ciel dégagé')
        ->assertJsonPath('data.temperature', 12.5)
        ->assertJsonPath('data.humidity', 70);

    Http::assertSent(fn (Request $request) => $request['q'] === 'Dijon,FR'
        && $request['appid'] === 'test-key'
        && $request['units'] === 'metric');
});

it('caches the weather between requests', function () {
    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'name' => 'Dijon', 'dt' => 1762768800, 'sys' => ['country' => 'FR'],
            'weather' => [['description' => 'pluie', 'icon' => '10d']],
            'main' => ['temp' => 8, 'feels_like' => 6, 'temp_min' => 7, 'temp_max' => 9, 'humidity' => 90, 'pressure' => 1008],
            'wind' => ['speed' => 5],
        ]),
    ]);

    $this->getJson('/api/weather/dijon')->assertOk();
    $this->getJson('/api/weather/dijon')->assertOk();

    Http::assertSentCount(1);
});

it('returns 503 when OpenWeather fails', function () {
    Http::fake([
        'api.openweathermap.org/*' => Http::response(['message' => 'Invalid API key'], 401),
    ]);

    $this->getJson('/api/weather/dijon')
        ->assertStatus(503)
        ->assertJsonPath('message', 'Le service météo est indisponible.');
});

it('shows the weather of Dijon on the home page', function () {
    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'name' => 'Dijon', 'dt' => 1762768800, 'sys' => ['country' => 'FR'],
            'weather' => [['description' => 'ciel dégagé', 'icon' => '01d']],
            'main' => ['temp' => 12.6, 'feels_like' => 11.2, 'temp_min' => 10, 'temp_max' => 14, 'humidity' => 70, 'pressure' => 1015],
            'wind' => ['speed' => 5],
        ]),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSeeText('Dijon (FR)')
        ->assertSeeText('Ciel dégagé')
        ->assertSeeText('Température : 13 °C')
        ->assertSeeText('Vent : 18 km/h');
});

it('shows a fallback message on the home page when OpenWeather fails', function () {
    Http::fake([
        'api.openweathermap.org/*' => Http::response(status: 500),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSeeText('La météo est momentanément indisponible.');
});
