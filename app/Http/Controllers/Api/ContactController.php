<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    private function findApplication(string $namespace)
    {
        return Application::where('namespace', $namespace)->first();
    }

    public function listContacts(Request $request, string $namespace)
    {
        $application = $this->findApplication($namespace);

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $query = Contact::where('application_id', $application->id);

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('active')) {
            $query->where('active', filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN));
        }

        $contacts = $query->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $contacts,
        ]);
    }

    public function store(Request $request, string $namespace)
    {
        $application = $this->findApplication($namespace);

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'phone2' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'category' => 'nullable|string|max:100',
            'active' => 'boolean',
            'payload' => 'nullable|array',
        ]);

        $contact = Contact::create([
            'application_id' => $application->id,
            'active' => true,
            ...$validated,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $contact->fresh(),
        ], 201);
    }

    public function update(Request $request, string $namespace, int $id)
    {
        $application = $this->findApplication($namespace);

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $contact = Contact::where('application_id', $application->id)->find($id);

        if (! $contact) {
            return response()->json(['message' => 'Contact not found'], 404);
        }

        $validated = $request->validate([
            'type' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:30',
            'phone2' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'category' => 'nullable|string|max:100',
            'active' => 'boolean',
            'payload' => 'nullable|array',
        ]);

        if (isset($validated['payload']) && $contact->payload) {
            $validated['payload'] = array_merge($contact->payload, $validated['payload']);
        }

        $contact->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $contact->fresh(),
        ]);
    }

    public function destroy(string $namespace, int $id)
    {
        $application = $this->findApplication($namespace);

        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $contact = Contact::where('application_id', $application->id)->find($id);

        if (! $contact) {
            return response()->json(['message' => 'Contact not found'], 404);
        }

        $contact->delete();

        return response()->json(['status' => 'success'], 200);
    }
}
