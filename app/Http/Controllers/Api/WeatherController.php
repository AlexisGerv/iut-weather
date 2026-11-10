<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenWeatherService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class WeatherController extends Controller
{
    /**
     * Météo actuelle de Dijon.
     */
    public function __invoke(OpenWeatherService $weather): JsonResponse
    {
        try {
            return response()->json(['data' => $weather->currentWeather('Dijon')]);
        } catch (RequestException|ConnectionException $e) {
            report($e);

            return response()->json([
                'message' => 'Le service météo est indisponible.',
            ], 503);
        }
    }
}
