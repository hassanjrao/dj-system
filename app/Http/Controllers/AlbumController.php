<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;

class AlbumController extends Controller
{
    public function index(Request $request)
    {
        $query = Album::with('client');
        
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        
        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'release_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $album = Album::create($validated);
        return response()->json($album->load('client'), 201);
    }

    public function show($id)
    {
        $album = Album::with(['client', 'assignments'])->findOrFail($id);
        return response()->json($album);
    }

    public function update(Request $request, $id)
    {
        $album = Album::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'release_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $album->update($validated);
        return response()->json($album->load('client'));
    }

    public function destroy($id)
    {
        $album = Album::findOrFail($id);
        $album->delete();
        return response()->json(['message' => 'Album deleted successfully']);
    }
}
