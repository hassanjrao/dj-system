<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Song;
use Illuminate\Support\Facades\Auth;

class SongController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $songs = Song::with(['album', 'musicType', 'musicGenre', 'musicKey', 'artists'])
            ->orderBy('name')
            ->get();

        return response()->json($songs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'version' => 'nullable|string|max:255',
            'album_id' => 'nullable|exists:albums,id',
            'music_type_id' => 'nullable|exists:music_types,id',
            'music_genre_id' => 'nullable|exists:music_genres,id',
            'bpm' => 'nullable|integer|min:1|max:999',
            'music_key_id' => 'nullable|exists:music_keys,id',
            'release_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'artists' => 'nullable|array',
            'artists.*' => 'exists:artists,id',
        ]);

        $song = Song::create($validated);

        // Handle artists
        if ($request->has('artists') && is_array($request->artists)) {
            foreach ($request->artists as $artistId) {
                if (!empty($artistId)) {
                    $song->artists()->attach($artistId);
                }
            }
        }

        return response()->json($song->load(['album', 'musicType', 'musicGenre', 'musicKey', 'artists']), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $song = Song::with(['album', 'musicType', 'musicGenre', 'musicKey', 'artists'])
            ->findOrFail($id);

        return response()->json($song);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $song = Song::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:255',
            'album_id' => 'nullable|exists:albums,id',
            'music_type_id' => 'nullable|exists:music_types,id',
            'music_genre_id' => 'nullable|exists:music_genres,id',
            'bpm' => 'nullable|integer|min:1|max:999',
            'music_key_id' => 'nullable|exists:music_keys,id',
            'release_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'artists' => 'nullable|array',
            'artists.*' => 'exists:artists,id',
        ]);

        $song->update($validated);

        // Handle artists
        if ($request->has('artists')) {
            if (is_array($request->artists)) {
                $song->artists()->sync($request->artists);
            } else {
                $song->artists()->detach();
            }
        }

        return response()->json($song->load(['album', 'musicType', 'musicGenre', 'musicKey', 'artists']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $song = Song::findOrFail($id);
        $song->delete();

        return response()->json(['message' => 'Song deleted successfully']);
    }
}
