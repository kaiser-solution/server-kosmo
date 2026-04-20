<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Cache;

class DynamicApiController extends Controller
{
    public function handle(string $namespace)
    {
        $cacheKey = "application_by_namespace_{$namespace}";
        $application = Cache::get($cacheKey, 'NOT_CACHED');

        if ($application === 'NOT_CACHED') {

            $model = Application::where('namespace', $namespace)->first();

            $application = $model?->toArray();
            $ttl = $application ? 86400 : 60;
            Cache::put($cacheKey, $application, $ttl);
        }

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        // dd($application);
        return response()->json([
            'status' => 'success',
            'application' => $application['name'],
            'namespace' => $application['namespace'],
            'endpoint' => $application['endpoint'],
        ]);
    }
}
