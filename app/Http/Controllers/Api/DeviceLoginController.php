<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        // Se o email e a senha forem fornecidos, valida credenciais completas
        if ($request->has('email') && $request->has('password')) {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'fingerprint' => 'required|string',
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => [__('The provided credentials are incorrect.')],
                ]);
            }

            // Restrição: apenas usuários normais (não administradores)
            if ($user->is_admin) {
                return response()->json([
                    'message' => __('Administrators must login via management panel.'),
                ], 403);
            }

            // Fingerprint logic: associate device with user
            $existingFingerprint = DeviceFingerprint::where('fingerprint', $request->fingerprint)->first();

            if ($existingFingerprint) {
                // Se o fingerprint existe, deve pertencer a este usuário
                if ($existingFingerprint->user_id !== $user->id) {
                    return response()->json([
                        'message' => __('This device is already associated with another account.'),
                    ], 403);
                }
            } else {
                // Associar novo fingerprint com o usuário
                DeviceFingerprint::create([
                    'user_id' => $user->id,
                    'fingerprint' => $request->fingerprint,
                ]);
            }
        } else {
            // Se apenas o fingerprint for fornecido
            $request->validate([
                'fingerprint' => 'required|string',
            ]);

            $existingFingerprint = DeviceFingerprint::where('fingerprint', $request->fingerprint)->first();

            if (! $existingFingerprint) {
                return response()->json([
                    'message' => __('Device not registered. Please login with your credentials first.'),
                ], 401);
            }

            $user = $existingFingerprint->user;

            // Restrição: apenas usuários normais (não administradores)
            if ($user->is_admin) {
                return response()->json([
                    'message' => __('Administrators must login via management panel.'),
                ], 403);
            }
        }

        // Create Sanctum token
        $token = $user->createToken($request->fingerprint)->plainTextToken;

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
                'permissions' => $user->permissions->pluck('slug')->toArray(),
            ],
        ]);
    }
}
