<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'department_user')
            ->withTimestamps();
    }

    public function deliverables()
    {
        return $this->hasMany(Deliverable::class);
    }

    /**
     * Get the statuses available for this department.
     */
    public function statuses()
    {
        return $this->hasMany(DepartmentStatus::class)->orderBy('sort_order');
    }
}
