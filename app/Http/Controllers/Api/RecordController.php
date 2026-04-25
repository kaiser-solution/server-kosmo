<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Record;
use App\Models\RecordType;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function types(string $namespace)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $types = RecordType::where('application_id', $application->id)
            ->where('active', true)
            ->get(['id', 'name', 'slug', 'description', 'schema']);

        return response()->json([
            'status' => 'success',
            'data' => $types,
        ]);
    }

    public function listByType(Request $request, string $namespace, string $typeSlug)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $recordType = RecordType::where('application_id', $application->id)
            ->where('slug', $typeSlug)
            ->where('active', true)
            ->first();

        if (! $recordType) {
            return response()->json(['message' => 'Record type not found'], 404);
        }

        $query = Record::where('application_id', $application->id)
            ->where('record_type_id', $recordType->id);

        if ($month = $request->query('month')) {
            $query->where(function ($q) use ($month) {
                $q->where('occurred_at', 'like', $month.'%')
                    ->orWhere('payload->refMonth', $month);
            });
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('payload->desc', 'like', '%'.$search.'%')
                    ->orWhere('payload->inst', 'like', '%'.$search.'%');
            });
        }

        if ($inst = $request->query('inst')) {
            $query->where('payload->inst', 'like', '%'.$inst.'%');
        }

        if ($type = $request->query('type')) {
            if ($type !== 'all') {
                $query->where('payload->type', $type);
            }
        }

        if ($status = $request->query('status')) {
            if ($status === 'active') {
                $query->where(function ($q) {
                    $q->whereNull('payload->isVoided')
                        ->orWhere('payload->isVoided', false);
                });
            } elseif ($status === 'voided') {
                $query->where('payload->isVoided', true);
            }
        }

        // Totais do período filtrado
        $totals = [
            'income' => 0,
            'expense' => 0,
        ];

        if ($request->query('with_totals')) {
            $allFiltered = (clone $query)->get();
            $totals['income'] = $allFiltered->filter(fn ($r) => ($r->payload['type'] ?? '') === 'income' && ! ($r->payload['isVoided'] ?? false))->sum(fn ($r) => (float) ($r->payload['val'] ?? 0));
            $totals['expense'] = $allFiltered->filter(fn ($r) => ($r->payload['type'] ?? '') === 'expense' && ! ($r->payload['isVoided'] ?? false))->sum(fn ($r) => (float) ($r->payload['val'] ?? 0));
        }

        $records = $query->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($request->query('per_page', 20));

        return response()->json([
            'status' => 'success',
            'type' => $recordType->only(['id', 'name', 'slug']),
            'totals' => $totals,
            'data' => $records,
        ]);
    }

    public function update(Request $request, string $namespace, string $typeSlug, int $id)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $record = Record::where('application_id', $application->id)
            ->where('id', $id)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $validated = $request->validate([
            'payload' => 'sometimes|array',
            'occurred_at' => 'sometimes|nullable|date',
        ]);

        if (isset($validated['payload'])) {
            $record->payload = array_merge($record->payload ?? [], $validated['payload']);
        }

        if (array_key_exists('occurred_at', $validated)) {
            $record->occurred_at = $validated['occurred_at'];
        }

        $record->save();

        return response()->json([
            'status' => 'success',
            'data' => $record,
        ]);
    }

    public function listInstitutions(string $namespace, string $typeSlug)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $recordType = RecordType::where('application_id', $application->id)
            ->where('slug', $typeSlug)
            ->first();

        if (! $recordType) {
            return response()->json(['message' => 'Record type not found'], 404);
        }

        $institutions = $recordType->schema['x-institutions'] ?? [];

        return response()->json([
            'status' => 'success',
            'data' => array_values($institutions),
        ]);
    }

    public function storeInstitution(Request $request, string $namespace, string $typeSlug)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $recordType = RecordType::where('application_id', $application->id)
            ->where('slug', $typeSlug)
            ->first();

        if (! $recordType) {
            return response()->json(['message' => 'Record type not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'defaultVal' => 'nullable|numeric',
            'dueDay' => 'nullable|integer|min:1|max:31',
        ]);

        $schema = $recordType->schema ?? [];
        $institutions = $schema['x-institutions'] ?? [];

        $exists = collect($institutions)->firstWhere('name', $validated['name']);
        if ($exists) {
            return response()->json(['message' => 'Institution already exists'], 422);
        }

        $institution = [
            'name' => $validated['name'],
            'category' => $validated['category'] ?? null,
            'defaultVal' => isset($validated['defaultVal']) ? (float) $validated['defaultVal'] : null,
            'dueDay' => isset($validated['dueDay']) ? (int) $validated['dueDay'] : null,
            'active' => true,
        ];

        $institutions[] = $institution;
        $schema['x-institutions'] = $institutions;
        $recordType->schema = $schema;
        $recordType->save();

        return response()->json([
            'status' => 'success',
            'data' => $institution,
        ], 201);
    }

    public function toggleInstitution(Request $request, string $namespace, string $typeSlug, string $name)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $recordType = RecordType::where('application_id', $application->id)
            ->where('slug', $typeSlug)
            ->first();

        if (! $recordType) {
            return response()->json(['message' => 'Record type not found'], 404);
        }

        $schema = $recordType->schema ?? [];
        $institutions = $schema['x-institutions'] ?? [];

        $found = false;
        $updated = null;
        foreach ($institutions as &$inst) {
            if ($inst['name'] === $name) {
                $inst['active'] = ! ($inst['active'] ?? true);
                $updated = $inst;
                $found = true;
                break;
            }
        }
        unset($inst);

        if (! $found) {
            return response()->json(['message' => 'Institution not found'], 404);
        }

        $schema['x-institutions'] = $institutions;
        $recordType->schema = $schema;
        $recordType->save();

        return response()->json([
            'status' => 'success',
            'data' => $updated,
        ]);
    }

    public function updateInstitution(Request $request, string $namespace, string $typeSlug, string $name)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $recordType = RecordType::where('application_id', $application->id)
            ->where('slug', $typeSlug)
            ->first();

        if (! $recordType) {
            return response()->json(['message' => 'Record type not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'defaultVal' => 'nullable|numeric',
            'dueDay' => 'nullable|integer|min:1|max:31',
        ]);

        $schema = $recordType->schema ?? [];
        $institutions = $schema['x-institutions'] ?? [];

        $found = false;
        $updated = null;

        // Se o nome mudou, verifica se o novo nome já existe
        if ($validated['name'] !== $name) {
            $exists = collect($institutions)->firstWhere('name', $validated['name']);
            if ($exists) {
                return response()->json(['message' => 'Institution with this name already exists'], 422);
            }
        }

        foreach ($institutions as &$inst) {
            if ($inst['name'] === $name) {
                $inst['name'] = $validated['name'];
                $inst['category'] = $validated['category'] ?? null;
                $inst['defaultVal'] = isset($validated['defaultVal']) ? (float) $validated['defaultVal'] : null;
                $inst['dueDay'] = isset($validated['dueDay']) ? (int) $validated['dueDay'] : null;
                $updated = $inst;
                $found = true;
                break;
            }
        }
        unset($inst);

        if (! $found) {
            return response()->json(['message' => 'Institution not found'], 404);
        }

        $schema['x-institutions'] = $institutions;
        $recordType->schema = $schema;
        $recordType->save();

        return response()->json([
            'status' => 'success',
            'data' => $updated,
        ]);
    }

    public function store(Request $request, string $namespace, string $typeSlug)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $recordType = RecordType::where('application_id', $application->id)
            ->where('slug', $typeSlug)
            ->where('active', true)
            ->first();

        if (! $recordType) {
            return response()->json(['message' => 'Record type not found'], 404);
        }

        $validated = $request->validate([
            'payload' => 'required|array',
            'occurred_at' => 'nullable|date',
        ]);

        $record = Record::create([
            'application_id' => $application->id,
            'record_type_id' => $recordType->id,
            'payload' => $validated['payload'],
            'occurred_at' => $validated['occurred_at'] ?? now(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $record,
        ], 201);
    }
}
