<?php

use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DeviceLoginController;
use App\Http\Controllers\Api\DynamicApiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Api\RegisterController;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/me/applications', function (Request $request) {
    $user = $request->user();
    $applications = Application::whereIn('id', function ($query) use ($user) {
        $query->select('application_id')
            ->from('plans')
            ->join('plan_user', 'plans.id', '=', 'plan_user.plan_id')
            ->where('plan_user.user_id', $user->id);
    })->get();

    return response()->json($applications);
})->middleware('auth:sanctum');

Route::get('/', function (Request $request) {
    return response()->json(['status' => 'ok']);
});

Route::post('/register', [RegisterController::class, 'store']);
Route::post('/device-login', [DeviceLoginController::class, 'login']);
Route::post('/profiles', [ProfileController::class, 'store'])->middleware('auth:sanctum');
Route::delete('/profiles/{id}', [ProfileController::class, 'destroy'])->middleware('auth:sanctum');

Route::get('/{namespace}/config', [AppConfigController::class, 'show']);
Route::put('/{namespace}/config/categories', [AppConfigController::class, 'updateCategories'])->middleware('auth:sanctum');
Route::get('/{namespace}/record-types', [RecordController::class, 'types']);
Route::get('/{namespace}/records/{typeSlug}', [RecordController::class, 'listByType']);
Route::post('/{namespace}/records/{typeSlug}', [RecordController::class, 'store']);
Route::patch('/{namespace}/records/{typeSlug}/{id}', [RecordController::class, 'update']);
Route::delete('/{namespace}/records/{typeSlug}/{id}', [RecordController::class, 'destroy']);
Route::get('/{namespace}/institutions/{typeSlug}', [RecordController::class, 'listInstitutions']);
Route::post('/{namespace}/institutions/{typeSlug}', [RecordController::class, 'storeInstitution']);
Route::put('/{namespace}/institutions/{typeSlug}/{name}', [RecordController::class, 'updateInstitution']);
Route::patch('/{namespace}/institutions/{typeSlug}/{name}', [RecordController::class, 'toggleInstitution']);
Route::get('/{namespace}/contacts', [ContactController::class, 'listContacts']);
Route::post('/{namespace}/contacts', [ContactController::class, 'store']);
Route::patch('/{namespace}/contacts/{id}', [ContactController::class, 'update']);
Route::delete('/{namespace}/contacts/{id}', [ContactController::class, 'destroy']);
Route::get('/{namespace}', [DynamicApiController::class, 'handle']);

Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');
