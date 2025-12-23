<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'version',
        'album_id',
        'music_type_id',
        'music_genre_id',
        'bpm',
        'music_key_id',
        'release_date',
        'completion_date',
    ];

    protected $casts = [
        'release_date' => 'date',
        'completion_date' => 'date',
    ];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function musicType()
    {
        return $this->belongsTo(MusicType::class);
    }

    public function musicGenre()
    {
        return $this->belongsTo(MusicGenre::class);
    }

    public function musicKey()
    {
        return $this->belongsTo(MusicKey::class);
    }

    public function artists()
    {
        return $this->belongsToMany(Artist::class, 'artist_song')
            ->withTimestamps();
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
