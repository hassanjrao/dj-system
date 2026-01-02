<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'completion_date' => 'date',
        'release_date' => 'date',
    ];

    protected $appends = ['assignment_id'];

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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    public function deliverables()
    {
        return $this->belongsToMany(Deliverable::class, 'assignment_deliverables')
            ->withPivot('status', 'notes')
            ->withTimestamps();
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

    public function status()
    {
        return $this->belongsTo(AssignmentStatus::class, 'assignment_status', 'code');
    }

    /**
     * Get formatted assignment ID with department initials
     * Format: [Department Initials][4 zeros][Assignment ID]
     * Example: MC00001 for Music Creation department, assignment ID 1
     */
    public function getAssignmentIdAttribute()
    {
        if (!$this->department) {
            return str_pad($this->id, 5, '0', STR_PAD_LEFT);
        }

        // Get first letter of each word in department name
        $words = explode(' ', $this->department->name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        // Format: [Initials][4 zeros][ID]
        // Pad ID to 5 digits total (4 zeros + ID)
        $paddedId = str_pad($this->id, 5, '0', STR_PAD_LEFT);

        return $initials . $paddedId;
    }
}
