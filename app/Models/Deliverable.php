<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignments()
    {
        return $this->belongsToMany(Assignment::class, 'assignment_deliverables')
            ->withPivot('completion_status_id', 'wave_upload_status_id', 'mp3_upload_status_id', 'notes')
            ->withTimestamps();
    }

    /**
     * Get the completion status for the pivot relationship.
     */
    public function completionStatus()
    {
        return $this->belongsTo(DeliverableStatus::class, 'pivot_completion_status_id');
    }

    /**
     * Get the wave upload status for the pivot relationship.
     */
    public function waveUploadStatus()
    {
        return $this->belongsTo(DeliverableStatus::class, 'pivot_wave_upload_status_id');
    }

    /**
     * Get the mp3 upload status for the pivot relationship.
     */
    public function mp3UploadStatus()
    {
        return $this->belongsTo(DeliverableStatus::class, 'pivot_mp3_upload_status_id');
    }
}
