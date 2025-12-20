<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;

class AlbumController extends Controller
{
    public function index(Request $request)
    {
        $query = Album::orderBy('name');
        $albums = $query->get();
        return response()->json($albums);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $album = Album::create($validated);
        return response()->json($album, 201);
    }

    public function show($id)
    {
        $album = Album::findOrFail($id);
        return response()->json($album);
    }

    public function update(Request $request, $id)
    {
        $album = Album::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $album->update($validated);
        return response()->json($album);
    }

    public function destroy($id)
    {
        $album = Album::findOrFail($id);
        $album->delete();
        return response()->json(['message' => 'Album deleted successfully'], 200);
    }
}
