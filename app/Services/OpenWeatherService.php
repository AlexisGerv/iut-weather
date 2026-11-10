<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OpenWeatherService
{
    /**
     * Récupère la météo actuelle d'une ville (mise en cache).
     *
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function currentWeather(string $city, string $country = 'FR'): array
    {
        $cacheKey = 'openweather.current.'.strtolower("{$city}.{$country}");

        return Cache::remember($cacheKey, config('services.openweather.cache_ttl'), function () use ($city, $country) {
            $data = Http::baseUrl(config('services.openweather.base_url'))
                ->timeout(10)
                ->get('/data/2.5/weather', [
                    'q' => "{$city},{$country}",
                    'appid' => config('services.openweather.key'),
                    'units' => 'metric',
                    'lang' => 'fr',
                ])
                ->throw()
                ->json();

            return [
                'city' => $data['name'],
                'country' => $data['sys']['country'],
                'description' => $data['weather'][0]['description'],
                'icon' => $data['weather'][0]['icon'],
                'temperature' => $data['main']['temp'],
                'feels_like' => $data['main']['feels_like'],
                'temp_min' => $data['main']['temp_min'],
                'temp_max' => $data['main']['temp_max'],
                'humidity' => $data['main']['humidity'],
                'pressure' => $data['main']['pressure'],
                'wind_speed' => $data['wind']['speed'],
                'observed_at' => date(DATE_ATOM, $data['dt']),
            ];
        });
    }

    /**
     * Récupère les coordonnées des villes correspondant à un nom (API /geo/1.0/direct, mise en cache).
     *
     * @return list<array{name: string, country: string, state: ?string, latitude: float, longitude: float}>
     *
     * @throws RequestException
     */
    public function coordinates(string $city, ?string $country = null, int $limit = 5): array
    {
        $query = $country ? "{$city},{$country}" : $city;
        $cacheKey = 'openweather.geo.'.strtolower("{$query}.{$limit}");

        return Cache::remember($cacheKey, config('services.openweather.cache_ttl'), function () use ($query, $limit) {
            $locations = Http::baseUrl(config('services.openweather.base_url'))
                ->timeout(10)
                ->get('/geo/1.0/direct', [
                    'q' => $query,
                    'limit' => $limit,
                    'appid' => config('services.openweather.key'),
                ])
                ->throw()
                ->json();

            return array_map(fn (array $location): array => [
                'name' => $location['local_names']['fr'] ?? $location['name'],
                'country' => $location['country'],
                'state' => $location['state'] ?? null,
                'latitude' => (float) $location['lat'],
                'longitude' => (float) $location['lon'],
            ], $locations);
        });
    }

    /**
     * Récupère les prévisions météo sur 5 jours, par pas de 3 heures (API /data/2.5/forecast, mise en cache).
     *
     * @return array{city: string, country: string, latitude: float, longitude: float, forecasts: list<array{forecast_at: string, description: string, icon: string, temperature: float, feels_like: float, temp_min: float, temp_max: float, humidity: int, pressure: int, wind_speed: float, precipitation_probability: float}>}
     *
     * @throws RequestException
     */
    public function forecast(float $latitude, float $longitude): array
    {
        $cacheKey = 'openweather.forecast.'.round($latitude, 4).'.'.round($longitude, 4);

        return Cache::remember($cacheKey, config('services.openweather.cache_ttl'), function () use ($latitude, $longitude) {
            $data = Http::baseUrl(config('services.openweather.base_url'))
                ->timeout(10)
                ->get('/data/2.5/forecast', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'appid' => config('services.openweather.key'),
                    'units' => 'metric',
                    'lang' => 'fr',
                ])
                ->throw()
                ->json();

            return [
                'city' => $data['city']['name'],
                'country' => $data['city']['country'],
                'latitude' => (float) $data['city']['coord']['lat'],
                'longitude' => (float) $data['city']['coord']['lon'],
                'forecasts' => array_map(fn (array $entry): array => [
                    'forecast_at' => date(DATE_ATOM, $entry['dt']),
                    'description' => $entry['weather'][0]['description'],
                    'icon' => $entry['weather'][0]['icon'],
                    'temperature' => $entry['main']['temp'],
                    'feels_like' => $entry['main']['feels_like'],
                    'temp_min' => $entry['main']['temp_min'],
                    'temp_max' => $entry['main']['temp_max'],
                    'humidity' => $entry['main']['humidity'],
                    'pressure' => $entry['main']['pressure'],
                    'wind_speed' => $entry['wind']['speed'],
                    'precipitation_probability' => $entry['pop'] ?? 0,
                ], $data['list']),
            ];
        });
    }

    /**
     * Récupère les prévisions météo d'une ville à partir de son nom, ou null si la ville est introuvable.
     *
     * @return array{city: string, country: string, latitude: float, longitude: float, forecasts: list<array<string, mixed>>}|null
     *
     * @throws RequestException
     */
    public function forecastForCity(string $city, ?string $country = null): ?array
    {
        $location = $this->coordinates($city, $country, 1)[0] ?? null;

        if ($location === null) {
            return null;
        }

        return $this->forecast($location['latitude'], $location['longitude']);
    }
}
