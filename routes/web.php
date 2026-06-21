<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    try {
        $connection = DB::connection('mysql');
        $database = $connection->selectOne('select database() as name');
        $version = $connection->selectOne('select version() as version');

        return response()->json([
            'status' => 'ok',
            'service' => '2026-hpe-api',
            'version' => '3.2.0',
            'database' => [
                'driver' => 'mysql',
                'connected' => true,
                'name' => $database?->name,
                'version' => $version?->version,
            ],
            'checkedAt' => now()->toISOString(),
        ]);
    } catch (\Throwable $error) {
        return response()->json([
            'status' => 'error',
            'service' => '2026-hpe-api',
            'version' => '3.2.0',
            'database' => [
                'driver' => 'mysql',
                'connected' => false,
            ],
            'message' => 'MySQL connection failed.',
            'error' => $error->getMessage(),
            'checkedAt' => now()->toISOString(),
        ], 503);
    }
});
