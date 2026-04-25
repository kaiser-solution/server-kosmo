<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Application extends BaseModel
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'name' => [
            'label' => 'Nome',
            'type' => 'text',
            'rules' => 'required|string|max:255',
        ],
        'namespace' => [
            'label' => 'Namespace',
            'type' => 'text',
            'rules' => 'required|string|max:255',
        ],
        'description' => [
            'label' => 'Descrição',
            'type' => 'text',
            'rules' => 'nullable|string',
        ],
        'endpoint' => [
            'label' => 'Endpoint',
            'type' => 'text',
            'rules' => 'required|string',
        ],
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'application_plan');
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    public function config()
    {
        return $this->hasOne(AppConfig::class);
    }

    public function recordTypes()
    {
        return $this->hasMany(RecordType::class);
    }

    public function records()
    {
        return $this->hasMany(Record::class);
    }
}
