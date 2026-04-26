<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppConfig;
use App\Models\Application;
use Illuminate\Http\Request;
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

            if (! $application) {
                return response()->json(['message' => 'Application not found'], 404);
            }

            $config = $application->config?->toArray() ?? [];
            $config['app_name'] = $application->name;

            $ttl = 86400;
            Cache::put($cacheKey, $config, $ttl);
        }

        if ($config === null) {
            return response()->json(['message' => 'Config not found'], 404);
        }

        $categories = $config['categories'] ?? [];

        return response()->json([
            'status' => 'success',
            'config' => [
                'name' => $config['app_name'] ?? null,
                'display_name' => $config['display_name'] ?? $config['app_name'] ?? null,
                'primary_color' => $config['primary_color'] ?? null,
                'secondary_color' => $config['secondary_color'] ?? null,
                'default_currency' => $config['default_currency'] ?? 'BRL',
                'categories' => $categories,
            ],
        ]);
    }

    public function updateCategories(Request $request, string $namespace)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $validated = $request->validate([
            'categories' => ['required', 'array'],
            'categories.*.name' => ['required', 'string', 'max:100'],
            'categories.*.color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $config = $application->config ?? new AppConfig;
        $config->application_id = $application->id;
        $config->categories = $validated['categories'];
        $config->save();

        Cache::forget("app_config_by_namespace_{$namespace}");

        return response()->json([
            'status' => 'success',
            'categories' => $validated['categories'],
        ]);
    }
}
