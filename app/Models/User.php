<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Abstract\BaseAuthenticableModel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends BaseAuthenticableModel
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected static array $fields = [
        'name' => [
            'label' => 'Nome',
            'type' => 'text',
            'rules' => 'required|string|max:255',
        ],
        'email' => [
            'label' => 'Email',
            'type' => 'email',
            'rules' => 'required|string|email|max:255',
        ],
        'password' => [
            'label' => 'Senha',
            'type' => 'password',
            'rules' => 'required|string|min:8',
        ],
        'phone' => [
            'label' => 'Telefone',
            'type' => 'text',
            'rules' => 'nullable|string|max:255',
        ],
        'is_admin' => [
            'label' => 'Administrador',
            'type' => 'boolean',
            'rules' => 'boolean',
        ],
        'regulatory_bodies' => [
            'label' => 'Órgãos Reguladores/Centralizadores',
            'type' => 'text',
            'rules' => 'nullable|string',
        ],
        'credentials' => [
            'label' => 'Credenciais',
            'type' => 'text',
            'rules' => 'nullable|string',
        ],
        'specialties' => [
            'label' => 'Especialidades',
            'type' => 'text',
            'rules' => 'nullable|string',
        ],
        'description' => [
            'label' => 'Descrição',
            'type' => 'textarea',
            'rules' => 'nullable|string',
        ],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the fingerprints associated with the user.
     *
     * @return HasMany<DeviceFingerprint, $this>
     */
    public function fingerprints(): HasMany
    {
        return $this->hasMany(DeviceFingerprint::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_user');
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'plans', 'id', 'application_id', 'id', 'id')
            ->join('plan_user', 'plans.id', '=', 'plan_user.plan_id')
            ->where('plan_user.user_id', $this->id)
            ->distinct();
    }

    /**
     * Get the profiles associated with the user.
     *
     * @return HasMany<UserProfile, $this>
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(UserProfile::class);
    }
}
