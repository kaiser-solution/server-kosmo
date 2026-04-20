<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\DeviceFingerprint;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DeviceLoginController extends Controller
{
    /**
     * Authenticate a third-party software device using email, password, and fingerprint.
     *
     *
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'fingerprint' => 'required|string',
            'app' => 'nullable|string|exists:applications,namespace',
            'email' => 'nullable|email',
            'password' => 'nullable|string',
        ]);

        $application = $request->filled('app')
            ? Application::where('namespace', $request->app)->first()
            : null;

        $user = $this->getUser($request, $application);

        if ($user->is_admin) {
            return response()->json([
                'message' => __('Administrators must login via management panel.'),
            ], 403);
        }

        $token = $user->createToken($request->fingerprint)->plainTextToken;

        $plans = $user->plans()->with('permissions')->get();
        $permissions = $plans->flatMap(fn ($plan) => $plan->permissions)
            ->pluck('slug')
            ->unique()
            ->values()
            ->toArray();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'regulatory_bodies' => $user->regulatory_bodies,
                'credentials' => $user->credentials,
                'specialties' => $user->specialties,
                'description' => $user->description,
                'is_admin' => $user->is_admin,
                'permissions' => $permissions,
            ],
        ]);
    }

    /**
     * Resolve the user based on either full credentials or fingerprint.
     *
     * @throws ValidationException
     */
    private function getUser(Request $request, ?Application $application): User
    {
        if ($request->filled(['email', 'password'])) {
            return $this->authenticateWithCredentials($request, $application);
        }

        return $this->authenticateWithFingerprint($request, $application);
    }

    /**
     * Authenticate using email and password, then associate the fingerprint.
     *
     * @throws ValidationException
     */
    private function authenticateWithCredentials(Request $request, ?Application $application): User
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('The provided credentials are incorrect.')],
            ]);
        }

        $this->syncFingerprint($user, $request->fingerprint, $application);

        return $user;
    }

    /**
     * Authenticate using only the fingerprint.
     */
    private function authenticateWithFingerprint(Request $request, ?Application $application): User
    {
        $query = DeviceFingerprint::where('fingerprint', $request->fingerprint);

        if ($application) {
            $query->where('application_id', $application->id);
        }

        $device = $query->first();

        if (! $device) {
            abort(response()->json([
                'message' => __('Device not registered. Please login with your credentials first.'),
            ], 401));
        }

        return $device->user;
    }

    /**
     * Sync or create the device fingerprint for the user.
     */
    private function syncFingerprint(User $user, string $fingerprint, ?Application $application): void
    {
        $query = DeviceFingerprint::where('fingerprint', $fingerprint);

        if ($application) {
            $query->where('application_id', $application->id);
        }

        $existing = $query->first();

        if ($existing && $existing->user_id !== $user->id) {
            abort(response()->json([
                'message' => __('This device is already associated with another account.'),
            ], 403));
        }

        if (! $existing) {
            DeviceFingerprint::create([
                'user_id' => $user->id,
                'application_id' => $application?->id,
                'fingerprint' => $fingerprint,
            ]);
        }
    }
}
