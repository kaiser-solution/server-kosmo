<?php

use App\Http\Controllers\Api\DeviceLoginController;
use App\Http\Controllers\Api\DynamicApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/', function (Request $request) {
    return response()->json(['status' => 'ok']);
});

Route::post('/device-login', [DeviceLoginController::class, 'login']);

Route::get('/{namespace}', [DynamicApiController::class, 'handle']);
