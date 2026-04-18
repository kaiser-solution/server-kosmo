<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceFingerprint extends BaseModel
{
    protected static array $fields = [
        'user_id' => 'int',
        'fingerprint' => 'string',
    ];

    /**
     * Get the user that owns the fingerprint.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
