<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.openweather.key' => 'test-key',
        'services.openweather.base_url' => 'https://api.openweathermap.org',
    ]);
});

/**
 * @return array<string, mixed>
 */
function fakeForecastResponse(): array
{
    return [
        'list' => [
            [
                'dt' => 1762779600,
                'main' => ['temp' => 11.3, 'feels_like' => 10.1, 'temp_min' => 10.2, 'temp_max' => 11.3, 'humidity' => 78, 'pressure' => 1016],
                'weather' => [['description' => 'nuageux', 'icon' => '04d']],
                'wind' => ['speed' => 4.1],
                'pop' => 0.2,
            ],
            [
                'dt' => 1762790400,
                'main' => ['temp' => 8.4, 'feels_like' => 6.9, 'temp_min' => 8.4, 'temp_max' => 8.4, 'humidity' => 85, 'pressure' => 1017],
                'weather' => [['description' => 'légère pluie', 'icon' => '10n']],
                'wind' => ['speed' => 3.2],
                'pop' => 0.6,
            ],
        ],
        'city' => [
            'name' => 'Dijon',
            'country' => 'FR',
            'coord' => ['lat' => 47.3216, 'lon' => 5.0415],
        ],
    ];
}

it('returns the forecast of a city using its coordinates', function () {
    Http::fake([
        'api.openweathermap.org/geo/1.0/direct*' => Http::response([
            ['name' => 'Dijon', 'lat' => 47.3215806, 'lon' => 5.0414701, 'country' => 'FR'],
        ]),
        'api.openweathermap.org/data/2.5/forecast*' => Http::response(fakeForecastResponse()),
    ]);

    $this->getJson('/api/forecast?city=Dijon&country=FR')
        ->assertOk()
        ->assertJsonPath('data.city', 'Dijon')
        ->assertJsonPath('data.country', 'FR')
        ->assertJsonCount(2, 'data.forecasts')
        ->assertJsonPath('data.forecasts.0.description', 'nuageux')
        ->assertJsonPath('data.forecasts.0.temperature', 11.3)
        ->assertJsonPath('data.forecasts.1.precipitation_probability', 0.6);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/data/2.5/forecast')
        && $request['lat'] == 47.3215806
        && $request['lon'] == 5.0414701
        && $request['appid'] === 'test-key'
        && $request['units'] === 'metric');
});

it('caches the forecast between requests', function () {
    Http::fake([
        'api.openweathermap.org/geo/1.0/direct*' => Http::response([
            ['name' => 'Dijon', 'lat' => 47.3215806, 'lon' => 5.0414701, 'country' => 'FR'],
        ]),
        'api.openweathermap.org/data/2.5/forecast*' => Http::response(fakeForecastResponse()),
    ]);

    $this->getJson('/api/forecast?city=Dijon')->assertOk();
    $this->getJson('/api/forecast?city=Dijon')->assertOk();

    Http::assertSentCount(2);
});

it('returns 404 when the city cannot be found', function () {
    Http::fake([
        'api.openweathermap.org/geo/1.0/direct*' => Http::response([]),
    ]);

    $this->getJson('/api/forecast?city=Nowhereville')
        ->assertNotFound()
        ->assertJsonPath('message', 'Ville introuvable.');

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/data/2.5/forecast'));
});

it('returns 503 when the forecast API fails', function () {
    Http::fake([
        'api.openweathermap.org/geo/1.0/direct*' => Http::response([
            ['name' => 'Dijon', 'lat' => 47.32, 'lon' => 5.04, 'country' => 'FR'],
        ]),
        'api.openweathermap.org/data/2.5/forecast*' => Http::response(status: 500),
    ]);

    $this->getJson('/api/forecast?city=Dijon')
        ->assertStatus(503)
        ->assertJsonPath('message', 'Le service météo est indisponible.');
});
