<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'department_id',
        'assigned_to_id',
        'assignment_name',
        'completion_date',
        'release_date',
        'release_timing',
        'reference_links',
        'status',
        'music_type_id',
        'song_name',
        'version_name',
        'album_id',
        'bpm',
        'music_key_id',
        'music_genre_id',
        'music_creation_status_id',
        'edit_type_id',
        'footage_type_id',
        'parent_assignment_id',
        'linked_song_assignment_id',
    ];

    protected $casts = [
        'completion_date' => 'date',
        'release_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function musicType()
    {
        return $this->belongsTo(MusicType::class);
    }

    public function musicKey()
    {
        return $this->belongsTo(MusicKey::class);
    }

    public function musicGenre()
    {
        return $this->belongsTo(MusicGenre::class);
    }

    public function musicCreationStatus()
    {
        return $this->belongsTo(MusicCreationStatus::class);
    }

    public function editType()
    {
        return $this->belongsTo(EditType::class);
    }

    public function footageType()
    {
        return $this->belongsTo(FootageType::class);
    }

    public function parentAssignment()
    {
        return $this->belongsTo(Assignment::class, 'parent_assignment_id');
    }

    public function childAssignments()
    {
        return $this->hasMany(Assignment::class, 'parent_assignment_id');
    }

    public function linkedSongAssignment()
    {
        return $this->belongsTo(Assignment::class, 'linked_song_assignment_id');
    }

    public function deliverables()
    {
        return $this->belongsToMany(Deliverable::class, 'assignment_deliverables')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }

    public function artists()
    {
        return $this->belongsToMany(Artist::class, 'assignment_artists')
            ->withTimestamps();
    }

    public function assignmentArtists()
    {
        return $this->hasMany(AssignmentArtist::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function parentRelationships()
    {
        return $this->hasMany(AssignmentRelationship::class, 'parent_assignment_id');
    }

    public function childRelationships()
    {
        return $this->hasMany(AssignmentRelationship::class, 'child_assignment_id');
    }
}
