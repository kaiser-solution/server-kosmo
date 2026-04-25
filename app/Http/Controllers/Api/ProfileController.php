<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'pin' => 'nullable|string|min:4|max:8',
        ]);

        $user = $request->user();

        $profile = UserProfile::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'avatar' => $request->avatar,
            'pin' => $request->pin,
        ]);

        return response()->json([
            'status' => 'success',
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'avatar' => $profile->avatar,
                'pin' => (bool) $profile->pin,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $profile = UserProfile::where('user_id', $user->id)->find($id);

        if (! $profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Perfil não encontrado.',
            ], 404);
        }

        $count = UserProfile::where('user_id', $user->id)->count();

        if ($count <= 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Você não pode excluir o seu único perfil.',
            ], 403);
        }

        $profile->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Perfil excluído com sucesso.',
        ]);
    }
}
