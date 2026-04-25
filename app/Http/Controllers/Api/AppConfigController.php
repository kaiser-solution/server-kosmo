<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Cache;

class AppConfigController extends Controller
{
    public function show(string $namespace)
    {
        $cacheKey = "app_config_by_namespace_{$namespace}";
        $config = Cache::get($cacheKey, 'NOT_CACHED');

        if ($config === 'NOT_CACHED') {
            $application = Application::where('namespace', $namespace)
                ->with('config')
                ->first();

            $config = $application?->config?->toArray();
            $ttl = $application ? 86400 : 60;
            Cache::put($cacheKey, $config, $ttl);
        }

        if ($config === null) {
            return response()->json(['message' => 'Config not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'config' => [
                'display_name' => $config['display_name'],
                'primary_color' => $config['primary_color'],
                'secondary_color' => $config['secondary_color'],
                'default_currency' => $config['default_currency'],
            ],
        ]);
    }
}
