<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $dbStatus = 'disconnected';
    $dbLatency = null;

    try {
        $start = microtime(true);
        DB::connection()->getPdo();
        $dbLatency = round((microtime(true) - $start) * 1000, 2);
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    return view('welcome', [
        'dbStatus' => $dbStatus,
        'dbLatency' => $dbLatency,
        'phpVersion' => PHP_VERSION,
        'laravelVersion' => app()->version(),
        'environment' => app()->environment(),
    ]);
});

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json(['status' => 'healthy', 'timestamp' => now()->toIso8601String()]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'unhealthy', 'error' => $e->getMessage()], 503);
    }
});

Route::get('/status', function () {
    $checks = [];

    try {
        $start = microtime(true);
        DB::connection()->getPdo();
        $checks['database'] = [
            'status' => 'connected',
            'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            'driver' => config('database.default'),
        ];
    } catch (\Exception $e) {
        $checks['database'] = [
            'status' => 'error',
            'error' => $e->getMessage(),
        ];
    }

    return response()->json([
        'app' => config('app.name'),
        'environment' => app()->environment(),
        'laravel' => app()->version(),
        'php' => PHP_VERSION,
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ]);
});
