<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Record;
use App\Models\RecordPattern;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function types(string $namespace)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $types = $application->recordTypes()
            ->with(['recordPatterns' => function ($query) {
                $query->select('record_patterns.id', 'record_patterns.name', 'record_patterns.defaults');
            }])
            ->where('status', 'active')
            ->get(['record_types.id', 'record_types.name', 'record_types.slug', 'record_types.description']);

        $types->transform(function ($type) {
            $type->schema = [
                'x-institutions' => $type->recordPatterns->map(function ($pattern) {
                    return array_merge(['name' => $pattern->name], $pattern->defaults ?? []);
                }),
            ];
            unset($type->recordPatterns);

            return $type;
        });

        return response()->json([
            'status' => 'success',
            'data' => $types,
        ]);
    }

    public function listInstitutions(string $namespace, string $typeSlug)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $recordType = $application->recordTypes()
            ->where('slug', $typeSlug)
            ->first();

        if (! $recordType) {
            return response()->json(['message' => 'Record type not found'], 404);
        }

        $institutions = $recordType->recordPatterns->map(function ($pattern) {
            return array_merge(['name' => $pattern->name], $pattern->defaults ?? []);
        });

        return response()->json([
            'status' => 'success',
            'data' => $institutions,
        ]);
    }

    public function storeInstitution(Request $request, string $namespace, string $typeSlug)
    {
        $application = Application::where('namespace', $namespace)->first();
        $recordType = $application->recordTypes()->where('slug', $typeSlug)->first();

        if (! $recordType) {
            return response()->json(['message' => 'Record type not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'nullable|string',
            'defaultVal' => 'nullable|numeric',
            'dueDay' => 'nullable|integer',
            'trackingSince' => 'nullable|string',
            'createdAt' => 'nullable|string',
        ]);

        $name = $validated['name'];
        unset($validated['name']);

        $pattern = RecordPattern::updateOrCreate(
            ['name' => $name],
            ['defaults' => $validated]
        );

        $recordType->recordPatterns()->syncWithoutDetaching([$pattern->id]);

        return response()->json([
            'status' => 'success',
            'data' => array_merge(['name' => $pattern->name], $pattern->defaults),
        ]);
    }

    public function updateInstitution(Request $request, string $namespace, string $typeSlug, string $name)
    {
        $application = Application::where('namespace', $namespace)->first();
        $recordType = $application->recordTypes()->where('slug', $typeSlug)->first();

        $pattern = $recordType->recordPatterns()->where('name', urldecode($name))->first();

        if (! $pattern) {
            return response()->json(['message' => 'Institution not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'category' => 'nullable|string',
            'defaultVal' => 'nullable|numeric',
            'dueDay' => 'nullable|integer',
            'active' => 'nullable|boolean',
        ]);

        if (isset($validated['name'])) {
            $pattern->name = $validated['name'];
            unset($validated['name']);
        }

        $pattern->defaults = array_merge($pattern->defaults ?? [], $validated);
        $pattern->save();

        return response()->json([
            'status' => 'success',
            'data' => array_merge(['name' => $pattern->name], $pattern->defaults),
        ]);
    }

    public function toggleInstitution(string $namespace, string $typeSlug, string $name)
    {
        $application = Application::where('namespace', $namespace)->first();
        $recordType = $application->recordTypes()->where('slug', $typeSlug)->first();

        $pattern = $recordType->recordPatterns()->where('name', urldecode($name))->first();

        if (! $pattern) {
            return response()->json(['message' => 'Institution not found'], 404);
        }

        $defaults = $pattern->defaults ?? [];
        $defaults['active'] = ! ($defaults['active'] ?? true);
        $pattern->defaults = $defaults;
        $pattern->save();

        return response()->json([
            'status' => 'success',
            'data' => array_merge(['name' => $pattern->name], $pattern->defaults),
        ]);
    }

    public function listByType(Request $request, string $namespace, string $typeSlug)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $recordType = $application->recordTypes()
            ->where('slug', $typeSlug)
            ->where('status', 'active')
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
            'record_type_id' => 'sometimes|exists:record_types,id',
        ]);

        if (isset($validated['payload'])) {
            $record->payload = array_merge($record->payload ?? [], $validated['payload']);
        }

        if (array_key_exists('occurred_at', $validated)) {
            $record->occurred_at = $validated['occurred_at'];
        }

        if (isset($validated['record_type_id'])) {
            $record->record_type_id = $validated['record_type_id'];
        }

        $record->save();

        return response()->json([
            'status' => 'success',
            'data' => $record,
        ]);
    }

    public function destroy(string $namespace, string $typeSlug, int $id)
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

        $record->delete();

        return response()->json(['status' => 'success'], 200);
    }

    public function store(Request $request, string $namespace, string $typeSlug)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $recordType = $application->recordTypes()
            ->where('slug', $typeSlug)
            ->where('status', 'active')
            ->first();

        if (! $recordType) {
            return response()->json(['message' => 'Record type not found'], 404);
        }

        $validated = $request->validate([
            'payload' => 'required|array',
            'occurred_at' => 'nullable|date',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
        ]);

        $payload = $validated['payload'];
        if (isset($validated['startDate'])) {
            $payload['startDate'] = $validated['startDate'];
        }
        if (isset($validated['endDate'])) {
            $payload['endDate'] = $validated['endDate'];
        }

        $record = Record::create([
            'application_id' => $application->id,
            'record_type_id' => $recordType->id,
            'payload' => $payload,
            'occurred_at' => $validated['occurred_at'] ?? now(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $record,
        ], 201);
    }
}
