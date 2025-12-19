<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentArtist extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'artist_id',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
}
