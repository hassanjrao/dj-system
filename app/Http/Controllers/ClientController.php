<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('name')->get();
        return response()->json($clients);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('manage-clients')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:clients,email',
            'phone' => 'nullable|string|max:255|unique:clients,phone',
            'notes' => 'nullable|string',
        ]);

        $client = Client::create($validated);
        return response()->json($client, 201);
    }

    public function show($id)
    {
        if (!Auth::user()->can('manage-clients')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $client = Client::with(['assignments', 'albums'])->findOrFail($id);
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('manage-clients')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $client->update($validated);
        return response()->json($client);
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('manage-clients')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $client = Client::findOrFail($id);
        $client->delete();
        return response()->json(['message' => 'Client deleted successfully']);
    }
}
