<?php

namespace App\Livewire\Abstracts;

use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

abstract class CrudComponent extends Component
{
    use WithPagination;

    public string $modelClass;

    public array $data = [];

    public ?int $editingId = null;

    public string $q = '';

    public string $pageTitle;

    public string $newButtonTitle;

    public string $searchPlaceholder;

    public string $editModalTitle;

    public string $createModalTitle;

    public ?string $modalSubtitle = null;

    public string $deleteModalTitle;

    public array $columnsToDisplay;

    public string $okButtonText = 'Salvar';

    public string $cancelButtonText = 'Cancelar';

    public string $actionsColumnTitle = 'Ações';

    public function fields(): array
    {
        $modelClass = $this->modelClass;

        return $modelClass::fields();
    }

    public function mount()
    {
        foreach ($this->fields() as $field => $config) {
            $type = $config['type'] ?? '';
            if ($type === 'checkbox-group') {
                $this->data[$field] = [];
            } elseif ($type === 'boolean') {
                $this->data[$field] = false;
            } else {
                $this->data[$field] = null;
            }
        }
    }

    #[Computed]
    public function items()
    {
        return $this->modelClass::query()
            ->when($this->q, fn ($q) => $q->where('name', 'like', "%{$this->q}%")
            )
            ->paginate(10);
    }

    #[Computed]
    public function editing()
    {
        return $this->editingId
            ? $this->modelClass::find($this->editingId)
            : null;
    }

    public function create()
    {
        $this->reset(['data', 'editingId']);
        $this->crudModal()->show();
    }

    public function edit($id)
    {
        $this->editingId = $id;

        foreach ($this->fields() as $field => $config) {
            $type = $config['type'] ?? '';
            if ($type === 'checkbox-group') {
                $this->data[$field] = $this->editing->$field->pluck('id')->map(fn ($id) => (string) $id)->toArray();
            } elseif ($type === 'password') {
                $this->data[$field] = '';
            } else {
                $this->data[$field] = $this->editing->{$field};
            }
        }

        $this->crudModal()->show();
    }

    public function save()
    {
        $this->validate($this->rules());

        if (method_exists($this, 'beforeSave')) {
            if ($this->beforeSave() === false) {
                return;
            }
        }

        $relationships = [];
        $dataToSave = $this->data;

        foreach ($this->fields() as $field => $config) {
            $type = $config['type'] ?? '';

            if ($type === 'checkbox-group') {
                $relationships[$field] = $dataToSave[$field];
                unset($dataToSave[$field]);
            }

            if ($type === 'password') {
                if ($this->editingId && empty($dataToSave[$field])) {
                    unset($dataToSave[$field]);
                } else {
                    $dataToSave[$field] = Hash::make($dataToSave[$field]);
                }
            }
        }

        if ($this->editing) {
            $this->editing->update($dataToSave);
            $model = $this->editing;
        } else {
            $model = $this->modelClass::create($dataToSave);
        }

        foreach ($relationships as $relation => $ids) {
            $model->$relation()->sync($ids ?? []);
        }

        if (method_exists($this, 'afterSave')) {
            $this->afterSave($model);
        }

        $this->crudModal()->close();
        $this->reset(['data', 'editingId']);
    }

    public function delete($id)
    {
        $this->modelClass::find($id)?->delete();
    }

    protected function rules()
    {
        return collect($this->fields())
            ->mapWithKeys(fn ($f, $k) => [
                "data.$k" => $f['rules'] ?? 'nullable',
            ])
            ->toArray();
    }

    protected function crudModal()
    {
        return $this->modal('crud-modal');
    }
}
