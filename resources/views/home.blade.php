<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'IUT Weather') }} - Real-time Weather & Forecast</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts and Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 font-sans antialiased selection:bg-cyan-500 selection:text-white flex flex-col justify-between">
    <!-- Background subtle gradient glows -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-cyan-500/15 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 -right-40 w-96 h-96 bg-blue-600/15 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Header Navigation -->
    <header class="border-b border-slate-800/80 bg-slate-900/70 backdrop-blur-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg shadow-cyan-500/20 group-hover:scale-105 transition duration-200">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                </div>
                <div>
                    <span class="font-bold text-lg tracking-tight text-white group-hover:text-cyan-400 transition">IUT Weather</span>
                    <span class="hidden sm:inline-block ms-2 text-xs font-medium px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">OpenWeather</span>
                </div>
            </a>

            <!-- Navigation Links / User Authentication -->
            <nav class="flex items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-300 hover:text-white px-3 py-2 rounded-lg hover:bg-slate-800 transition">
                            Dashboard
                        </a>
                        <a href="{{ route('profile.edit') }}" class="text-sm font-medium text-slate-300 hover:text-white px-3 py-2 rounded-lg hover:bg-slate-800 transition">
                            {{ Auth::user()->name }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-red-400 hover:text-red-300 px-3 py-2 rounded-lg hover:bg-red-500/10 transition">
                                Log Out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-300 hover:text-white px-3 py-2 rounded-lg hover:bg-slate-800 transition">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="text-sm font-medium text-white bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 px-4 py-2 rounded-lg shadow-md shadow-cyan-500/20 transition">
                                Register
                            </a>
                        @endif
                    @endauth
                @endif
            </nav>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full space-y-10">

        <!-- Api Key Notification Alert (Demo mode warning) -->
        @if ($isMock || ! $hasApiKey)
            <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 sm:p-5 flex items-start gap-4 text-amber-200">
                <div class="p-1 rounded-lg bg-amber-500/20 text-amber-300 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="text-sm space-y-1">
                    <p class="font-semibold text-amber-100">OpenWeather API Demonstration Mode</p>
                    <p class="text-amber-300/90 leading-relaxed">
                        No <code class="bg-amber-950/60 text-amber-200 px-1.5 py-0.5 rounded text-xs font-mono">OPENWEATHER_API_KEY</code> was detected in <code class="bg-amber-950/60 text-amber-200 px-1.5 py-0.5 rounded text-xs font-mono">.env</code>. You are currently viewing realistic simulated weather data. Provide your OpenWeather API key to activate live data.
                    </p>
                </div>
            </div>
        @endif

        <!-- Hero Section & City Search Form -->
        <section class="text-center max-w-3xl mx-auto space-y-6">
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-white">
                Accurate Weather <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">& Forecasts</span>
            </h1>
            <p class="text-slate-400 text-base sm:text-lg">
                Search any city worldwide for real-time conditions, 5-day forecasts, and direct CSV data exports.
            </p>

            <!-- Search Bar -->
            <form action="{{ route('home') }}" method="GET" class="relative max-w-2xl mx-auto flex items-center shadow-2xl">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        name="city"
                        value="{{ $city }}"
                        placeholder="Search for a city (e.g. Paris, London, Tokyo, New York...)"
                        required
                        class="w-full ps-11 pe-28 py-3.5 bg-slate-800/90 border border-slate-700 rounded-2xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition shadow-inner"
                    />
                    <button
                        type="submit"
                        class="absolute end-2 inset-y-2 px-5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-medium text-sm rounded-xl transition flex items-center gap-1.5 shadow-md shadow-cyan-500/20"
                    >
                        Search
                    </button>
                </div>
            </form>

            <!-- Quick Popular Cities Suggestions -->
            <div class="flex items-center justify-center flex-wrap gap-2 pt-2 text-xs text-slate-400">
                <span class="font-medium text-slate-400">Popular:</span>
                @foreach (['Paris', 'London', 'Tokyo', 'New York', 'Marseille', 'Montreal'] as $popularCity)
                    <a
                        href="{{ route('home', ['city' => $popularCity]) }}"
                        class="px-3 py-1 rounded-full bg-slate-800/80 hover:bg-slate-700 border border-slate-700/80 hover:border-cyan-500/40 text-slate-300 hover:text-cyan-400 transition"
                    >
                        {{ $popularCity }}
                    </a>
                @endforeach
            </div>
        </section>

        @if ($weather)
            <!-- Current Weather Card -->
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Condition Card -->
                <div class="lg:col-span-2 rounded-3xl bg-gradient-to-br from-slate-800/90 to-slate-850/90 border border-slate-700/70 p-6 sm:p-8 backdrop-blur-xl relative overflow-hidden shadow-2xl flex flex-col justify-between">
                    <!-- Subtle background badge -->
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-cyan-500/5 rounded-full blur-2xl pointer-events-none"></div>

                    <!-- Card Header -->
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                                    {{ $weather['name'] ?? $city }}
                                </h2>
                                @if (isset($weather['sys']['country']))
                                    <span class="px-2.5 py-0.5 rounded-md bg-slate-700/80 text-cyan-400 text-xs font-bold uppercase tracking-wider">
                                        {{ $weather['sys']['country'] }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-400 mt-1">
                                {{ \Carbon\Carbon::createFromTimestamp($weather['dt'] ?? time())->format('l, F j, Y • H:i') }}
                            </p>
                        </div>

                        <!-- CSV Export Button -->
                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('weather.export', ['city' => $city]) }}"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-700/70 hover:bg-slate-700 border border-slate-600/60 text-slate-200 hover:text-white text-xs sm:text-sm font-medium transition shadow-sm"
                                title="Download 5-Day Forecast as CSV"
                            >
                                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                <span>Export Forecast (CSV)</span>
                            </a>
                        </div>
                    </div>

                    <!-- Temperature & Condition Hero -->
                    <div class="my-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                        <div class="flex items-baseline gap-3">
                            <span class="text-6xl sm:text-7xl font-extrabold tracking-tighter text-white">
                                {{ round($weather['main']['temp'] ?? 0) }}°
                            </span>
                            <span class="text-2xl text-slate-400 font-medium">C</span>
                        </div>

                        <div class="flex items-center gap-4">
                            @php
                                $iconCode = $weather['weather'][0]['icon'] ?? '01d';
                                $iconUrl = "https://openweathermap.org/img/wn/{$iconCode}@4x.png";
                            @endphp
                            <img src="{{ $iconUrl }}" alt="{{ $weather['weather'][0]['description'] ?? 'Weather icon' }}" class="w-20 h-20 -my-4 drop-shadow-md">
                            <div>
                                <div class="text-xl font-semibold text-white capitalize">
                                    {{ $weather['weather'][0]['description'] ?? 'Clear Sky' }}
                                </div>
                                <div class="text-sm text-slate-400">
                                    Feels like {{ round($weather['main']['feels_like'] ?? 0) }}°C
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Min/Max & Coordinates Footer -->
                    <div class="pt-6 border-t border-slate-700/60 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-400">
                        <div class="flex items-center gap-4">
                            <span>Min: <strong class="text-slate-200">{{ round($weather['main']['temp_min'] ?? 0) }}°C</strong></span>
                            <span>Max: <strong class="text-slate-200">{{ round($weather['main']['temp_max'] ?? 0) }}°C</strong></span>
                        </div>
                        @if (isset($weather['coord']))
                            <div class="text-slate-500 font-mono">
                                Lat: {{ number_format($weather['coord']['lat'], 2) }}° • Lon: {{ number_format($weather['coord']['lon'], 2) }}°
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Weather Metrics Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Humidity -->
                    <div class="rounded-2xl bg-slate-800/80 border border-slate-700/70 p-5 backdrop-blur-md flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-xs uppercase font-semibold tracking-wider">Humidity</span>
                            <svg class="w-5 h-5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a6.75 6.75 0 0 0 6.75-6.75c0-3.728-6.75-11.25-6.75-11.25S5.25 10.522 5.25 14.25A6.75 6.75 0 0 0 12 21Z" />
                            </svg>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-bold text-white">{{ $weather['main']['humidity'] ?? 0 }}%</div>
                            <div class="text-xs text-slate-400 mt-1">Relative humidity</div>
                        </div>
                    </div>

                    <!-- Wind Speed -->
                    <div class="rounded-2xl bg-slate-800/80 border border-slate-700/70 p-5 backdrop-blur-md flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-xs uppercase font-semibold tracking-wider">Wind</span>
                            <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-bold text-white">{{ $weather['wind']['speed'] ?? 0 }} <span class="text-sm font-normal text-slate-400">m/s</span></div>
                            <div class="text-xs text-slate-400 mt-1">Wind speed</div>
                        </div>
                    </div>

                    <!-- Pressure -->
                    <div class="rounded-2xl bg-slate-800/80 border border-slate-700/70 p-5 backdrop-blur-md flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-xs uppercase font-semibold tracking-wider">Pressure</span>
                            <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m9-9H3" />
                            </svg>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-bold text-white">{{ $weather['main']['pressure'] ?? 0 }} <span class="text-sm font-normal text-slate-400">hPa</span></div>
                            <div class="text-xs text-slate-400 mt-1">Atmospheric</div>
                        </div>
                    </div>

                    <!-- Visibility -->
                    <div class="rounded-2xl bg-slate-800/80 border border-slate-700/70 p-5 backdrop-blur-md flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-xs uppercase font-semibold tracking-wider">Visibility</span>
                            <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-bold text-white">{{ round(($weather['visibility'] ?? 10000) / 1000, 1) }} <span class="text-sm font-normal text-slate-400">km</span></div>
                            <div class="text-xs text-slate-400 mt-1">Horizontal range</div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <!-- 5-Day Weather Forecast Section -->
        @if (! empty($forecast))
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl sm:text-2xl font-bold text-white">5-Day Weather Forecast</h3>
                        <p class="text-sm text-slate-400">Upcoming daily predictions for {{ $weather['name'] ?? $city }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    @foreach ($forecast as $day)
                        <div class="rounded-2xl bg-slate-800/80 border border-slate-700/70 p-5 flex flex-col justify-between hover:border-cyan-500/40 hover:-translate-y-1 transition duration-200">
                            <!-- Day & Date -->
                            <div>
                                <div class="text-sm font-bold text-white">{{ $day['day_name'] }}</div>
                                <div class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($day['date'])->format('M d') }}</div>
                            </div>

                            <!-- Weather Icon & Description -->
                            <div class="my-4 flex items-center gap-2">
                                <img
                                    src="https://openweathermap.org/img/wn/{{ $day['icon'] }}@2x.png"
                                    alt="{{ $day['description'] }}"
                                    class="w-14 h-14 -ms-2"
                                />
                                <div>
                                    <div class="text-lg font-bold text-white">{{ $day['temp'] }}°C</div>
                                    <div class="text-xs text-slate-400 capitalize truncate max-w-[100px]">{{ $day['description'] }}</div>
                                </div>
                            </div>

                            <!-- Min / Max and Humidity -->
                            <div class="pt-3 border-t border-slate-700/60 text-xs text-slate-400 flex items-center justify-between">
                                <span>{{ $day['temp_min'] }}° / {{ $day['temp_max'] }}°</span>
                                <span class="flex items-center gap-1 text-cyan-400">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a6.75 6.75 0 0 0 6.75-6.75c0-3.728-6.75-11.25-6.75-11.25S5.25 10.522 5.25 14.25A6.75 6.75 0 0 0 12 21Z" />
                                    </svg>
                                    {{ $day['humidity'] }}%
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Application Features Overview -->
        <section class="pt-8 border-t border-slate-800 space-y-6">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <h3 class="text-2xl font-bold text-white">Full-Featured Weather Platform</h3>
                <p class="text-sm text-slate-400">Built according to the project specifications with Laravel, Breeze, and OpenWeather.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Feature 1 -->
                <div class="rounded-2xl bg-slate-800/60 border border-slate-700/60 p-5 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-white text-base">OpenWeather API</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Dedicated service encapsulating geocoding, real-time weather, and 5-day forecasts with high-performance Laravel Cache.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="rounded-2xl bg-slate-800/60 border border-slate-700/60 p-5 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-white text-base">Saved Places</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Save multiple cities, designate a unique favorite place, and monitor customized weather reports directly from your dashboard.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="rounded-2xl bg-slate-800/60 border border-slate-700/60 p-5 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-white text-base">Daily Reports</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Automated morning emails scheduled via Laravel Artisan scheduler at 06:00 with attached CSV forecast spreadsheets.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="rounded-2xl bg-slate-800/60 border border-slate-700/60 p-5 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-white text-base">REST API (/api/v1)</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Complete JSON API Resource endpoints authenticated with Sanctum personal tokens, fully compatible with Postman.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-950/60 py-8 mt-12 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-400">IUT Weather App</span>
                <span>•</span>
                <span>Laravel &amp; OpenWeather Project</span>
            </div>
            <div class="flex items-center gap-4 text-slate-400">
                <span>Built with Laravel Breeze &amp; Alpine.js</span>
            </div>
        </div>
    </footer>
</body>
</html>