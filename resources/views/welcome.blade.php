<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} on Zerops</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg w-full">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-8 text-center border-b border-gray-100">
                <h1 class="text-2xl font-bold text-gray-900">Laravel on Zerops</h1>
                <p class="mt-2 text-gray-500 text-sm">{{ config('app.name') }} &mdash; {{ app()->environment() }}</p>
            </div>
            <div class="divide-y divide-gray-100">
                <div class="flex items-center justify-between px-6 py-4">
                    <span class="text-sm text-gray-600">PHP</span>
                    <span class="text-sm font-mono text-gray-900">{{ $phpVersion }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <span class="text-sm text-gray-600">Laravel</span>
                    <span class="text-sm font-mono text-gray-900">{{ $laravelVersion }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <span class="text-sm text-gray-600">Database</span>
                    <div class="flex items-center gap-2">
                        @if($dbStatus === 'connected')
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-sm text-gray-900">{{ $dbLatency }}ms</span>
                        @else
                            <span class="inline-block w-2 h-2 rounded-full bg-red-500"></span>
                            <span class="text-sm text-red-600">{{ $dbStatus }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <span class="text-sm text-gray-600">Environment</span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                        {{ $environment === 'production' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $environment }}
                    </span>
                </div>
            </div>
        </div>
        <p class="mt-4 text-center text-xs text-gray-400">
            <a href="/health" class="hover:text-gray-600">/health</a>
            &middot;
            <a href="/status" class="hover:text-gray-600">/status</a>
        </p>
    </div>
</body>
</html>
