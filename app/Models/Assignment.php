<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Assignment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'completion_date' => 'date',
        'release_date' => 'date',
    ];

    protected $appends = ['assignment_id'];

    /**
     * Boot the model and set up event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-assign department sequence number when creating new assignment
        static::creating(function ($assignment) {
            if ($assignment->department_id && !$assignment->department_sequence_number) {
                // Use transaction with row locking to prevent race conditions
                DB::transaction(function () use ($assignment) {
                    $maxSeq = static::where('department_id', $assignment->department_id)
                        ->lockForUpdate()
                        ->max('department_sequence_number') ?? 0;
                    $assignment->department_sequence_number = $maxSeq + 1;
                }, 5); // 5 second timeout to prevent deadlock
            }
        });
    }

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
     * Format: [Department Initials][5-digit sequence]
     * Example: MC00001 for Music Creation department, sequence 1
     */
    public function getAssignmentIdAttribute()
    {
        // Fallback to global ID if department or sequence number is missing
        if (!$this->department || !$this->department_sequence_number) {
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

        // Format: [Initials][5-digit padded sequence]
        $paddedSeq = str_pad($this->department_sequence_number, 5, '0', STR_PAD_LEFT);

        return $initials . $paddedSeq;
    }

    /**
     * Scope to restrict MUSIC CREATION assignments for users with role 'user'
     * Users cannot view MUSIC CREATION assignments not created by them that have status not equal to COMPLETED
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User|null $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRestrictMusicCreationForUsers($query, $user = null)
    {
        if (!$user || !$user->hasRole('user')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->whereHas('department', function ($deptQuery) {
                $deptQuery->where('slug', '!=', 'music-creation');
            })
            ->orWhere(function ($musicQuery) use ($user) {
                $musicQuery->whereHas('department', function ($deptQuery) {
                    $deptQuery->where('slug', 'music-creation');
                })
                ->where(function ($subQuery) use ($user) {
                    $subQuery->where('created_by', $user->id)
                             ->orWhere('assignment_status', 'completed');
                });
            });
        });
    }

    /**
     * Get formatted completion date
     * Returns: "Fri, Jan 28" format or null if no completion_date
     *
     * @return string|null
     */
    public function getFormattedCompletionDate()
    {
        if (!$this->completion_date) {
            return null;
        }

        return $this->completion_date->format('D, M j');
    }

    /**
     * Get completion date days remaining/overdue as formatted string
     * Returns: "X days overdue", "Due today", "1 day to go", or "X days to go"
     *
     * @param Carbon|null $referenceDate Optional reference date (defaults to today)
     * @return string|null
     */
    public function getCompletionDateDays($referenceDate = null)
    {
        if (!$this->completion_date) {
            return null;
        }

        $today = $referenceDate ?: Carbon::today();
        $daysRemaining = $today->diffInDays($this->completion_date, false);

        if ($daysRemaining < 0) {
            return abs($daysRemaining) . ' days overdue';
        } elseif ($daysRemaining == 0) {
            return 'Due today';
        } elseif ($daysRemaining == 1) {
            return '1 day to go';
        } else {
            return $daysRemaining . ' days to go';
        }
    }
}
