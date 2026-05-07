<?php

namespace App\Livewire\Admin;

use App\Models\Record;
use App\Models\RecordType;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RecordTypeDefaults extends Component
{
    public $recordTypes;

    public ?int $managingDefaultsRecordTypeId = null;

    public array $currentRecordTypeDefaults = [];

    public string $newDefaultName = '';

    public ?float $newDefaultValue = null;

    public string $newDefaultType = 'income';

    public string $newDefaultCategory = '';

    public function mount()
    {
        $this->recordTypes = RecordType::all();
    }

    public function manageDefaults($recordTypeId)
    {
        $this->managingDefaultsRecordTypeId = $recordTypeId;
        $recordType = RecordType::find($recordTypeId);
        $schema = $recordType->schema ?? [];
        $this->currentRecordTypeDefaults = $schema['x-institutions'] ?? [];

        $this->modal('record-type-defaults-modal')->show();
    }

    public function addDefault()
    {
        $this->validate([
            'newDefaultName' => 'required|string|max:255',
            'newDefaultValue' => 'nullable|numeric',
            'newDefaultType' => 'required|string',
            'newDefaultCategory' => 'nullable|string',
        ]);

        $this->currentRecordTypeDefaults[] = [
            'name' => $this->newDefaultName,
            'defaultVal' => $this->newDefaultValue,
            'type' => $this->newDefaultType,
            'category' => $this->newDefaultCategory,
            'enabled_in_registration' => true,
        ];

        $this->reset(['newDefaultName', 'newDefaultValue', 'newDefaultType', 'newDefaultCategory']);
    }

    public function toggleEnabled(int $index)
    {
        $this->currentRecordTypeDefaults[$index]['enabled_in_registration'] = ! ($this->currentRecordTypeDefaults[$index]['enabled_in_registration'] ?? false);
    }

    public function removeDefault(int $index)
    {
        $defaults = $this->currentRecordTypeDefaults;
        array_splice($defaults, $index, 1);
        $this->currentRecordTypeDefaults = $defaults;
    }

    public function saveDefaults()
    {
        $recordType = RecordType::find($this->managingDefaultsRecordTypeId);
        $schema = $recordType->schema ?? [];
        $schema['x-institutions'] = $this->currentRecordTypeDefaults;

        $recordType->update(['schema' => $schema]);

        $this->modal('record-type-defaults-modal')->close();
    }

    public function generateRecordsFromDefaults()
    {
        $recordType = RecordType::find($this->managingDefaultsRecordTypeId);
        $defaults = $recordType->schema['x-institutions'] ?? [];

        foreach ($defaults as $default) {
            Record::create([
                'application_id' => $recordType->application_id,
                'record_type_id' => $recordType->id,
                'payload' => [
                    'inst' => $default['name'],
                    'val' => $default['defaultVal'] ?? 0,
                    'type' => $default['type'],
                    'cat' => $default['category'],
                ],
            ]);
        }

        $this->modal('record-type-defaults-modal')->close();
    }

    public function render()
    {
        return view('pages.admin.record-type-defaults');
    }
}
