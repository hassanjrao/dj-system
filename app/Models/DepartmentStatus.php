<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'code',
        'name',
        'is_completed',
        'is_default',
        'sort_order',
        'color',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the department that owns this status.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Scope to get only completed statuses.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope to get only active (non-completed) statuses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope to get statuses for a specific department.
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId)->orderBy('sort_order');
    }

    /**
     * Scope to get the default status.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
