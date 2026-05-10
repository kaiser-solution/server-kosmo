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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/me/applications', function (Request $request) {
        $user = $request->user();
        $applications = Application::whereIn('id', function ($query) use ($user) {
            $query->select('application_id')
                ->from('plans')
                ->join('plan_user', 'plans.id', '=', 'plan_user.plan_id')
                ->where('plan_user.user_id', $user->id);
        })->get();

        return response()->json($applications);
    });

    Route::post('/profiles', [ProfileController::class, 'store']);
    Route::delete('/profiles/{id}', [ProfileController::class, 'destroy']);

    Route::prefix('/{namespace}')->group(function () {
        Route::get('/config', [AppConfigController::class, 'show']);
        Route::put('/config/categories', [AppConfigController::class, 'updateCategories']);
        Route::get('/record-types', [RecordController::class, 'types']);
        Route::get('/record-types/{typeSlug}/institutions', [RecordController::class, 'listInstitutions']);
        Route::post('/record-types/{typeSlug}/institutions', [RecordController::class, 'storeInstitution']);
        Route::put('/record-types/{typeSlug}/institutions/{name}', [RecordController::class, 'updateInstitution']);
        Route::patch('/record-types/{typeSlug}/institutions/{name}', [RecordController::class, 'toggleInstitution']);
        Route::get('/records/{typeSlug}', [RecordController::class, 'listByType']);
        Route::post('/records/{typeSlug}', [RecordController::class, 'store']);
        Route::patch('/records/{typeSlug}/{id}', [RecordController::class, 'update']);
        Route::delete('/records/{typeSlug}/{id}', [RecordController::class, 'destroy']);
        Route::get('/contacts', [ContactController::class, 'listContacts']);
        Route::post('/contacts', [ContactController::class, 'store']);
        Route::patch('/contacts/{id}', [ContactController::class, 'update']);
        Route::delete('/contacts/{id}', [ContactController::class, 'destroy']);
        Route::get('/', [DynamicApiController::class, 'handle']);
    });
});

Route::get('/', function (Request $request) {
    return response()->json(['status' => 'ok']);
});

Route::post('/register', [RegisterController::class, 'store']);
Route::post('/device-login', [DeviceLoginController::class, 'login']);

Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');
