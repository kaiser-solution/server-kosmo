<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Database\Factories\UserProfileFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends BaseModel
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'user_id' => [
            'label' => 'Usuário',
            'type' => 'select',
            'rules' => 'required|exists:users,id',
        ],
        'name' => [
            'label' => 'Nome do Perfil',
            'type' => 'text',
            'rules' => 'required|string|max:255',
        ],
        'avatar' => [
            'label' => 'Avatar',
            'type' => 'text',
            'rules' => 'nullable|string|max:255',
        ],
        'pin' => [
            'label' => 'PIN',
            'type' => 'password',
            'rules' => 'nullable|string|min:4|max:8',
        ],
    ];

    protected function casts(): array
    {
        return [
            'pin' => 'hashed',
        ];
    }

    /**
     * Get the user that owns the profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the permissions inherited from the parent user's plans.
     *
     * @return array<string>
     */
    public function getPermissions(): array
    {
        return $this->user
            ->plans()
            ->with('permissions')
            ->get()
            ->flatMap(fn (Plan $plan) => $plan->permissions)
            ->pluck('slug')
            ->unique()
            ->values()
            ->toArray();
    }
}
