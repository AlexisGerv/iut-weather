<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenWeatherService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    /**
     * Prévisions météo d'une ville.
     */
    public function __invoke(Request $request, OpenWeatherService $weather): JsonResponse
    {
        $validated = $request->validate([
            'city' => ['required', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        try {
            $forecast = $weather->forecastForCity($validated['city'], $validated['country'] ?? null);
        } catch (RequestException|ConnectionException $e) {
            report($e);

            return response()->json([
                'message' => 'Le service météo est indisponible.',
            ], 503);
        }

        if ($forecast === null) {
            return response()->json([
                'message' => 'Ville introuvable.',
            ], 404);
        }

        return response()->json(['data' => $forecast]);
    }
}
