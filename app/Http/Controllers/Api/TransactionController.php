<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function list(string $namespace)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $transactions = Transaction::where('application_id', $application->id)
            ->orderByDesc('occurred_at')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ]);
    }

    public function store(Request $request, string $namespace)
    {
        $application = Application::where('namespace', $namespace)->first();

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $validated = $request->validate([
            'type' => 'required|in:expense,income,transfer',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'occurred_at' => 'required|date',
            'metadata' => 'nullable|array',
        ]);

        $transaction = Transaction::create([
            ...$validated,
            'application_id' => $application->id,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $transaction,
        ], 201);
    }
}
