<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Record;
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
            ->where('status', 'active')
            ->get(['record_types.id', 'record_types.name', 'record_types.slug', 'record_types.description']);

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
