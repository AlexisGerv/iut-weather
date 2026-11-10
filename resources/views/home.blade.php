<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Météo actuelle
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($weather)
                        <h3 class="text-lg font-semibold">{{ $weather['city'] }} ({{ $weather['country'] }})</h3>
                        <p class="flex items-center">
                            <img src="https://openweathermap.org/img/wn/{{ $weather['icon'] }}@2x.png" alt="{{ $weather['description'] }}">
                            {{ ucfirst($weather['description']) }}
                        </p>
                        <ul>
                            <li>Température : {{ round($weather['temperature']) }} °C (ressenti {{ round($weather['feels_like']) }} °C)</li>
                            <li>Min / Max : {{ round($weather['temp_min']) }} °C / {{ round($weather['temp_max']) }} °C</li>
                            <li>Humidité : {{ $weather['humidity'] }} %</li>
                            <li>Pression : {{ $weather['pressure'] }} hPa</li>
                            <li>Vent : {{ round($weather['wind_speed'] * 3.6) }} km/h</li>
                        </ul>
                    @else
                        <p>La météo est momentanément indisponible.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
