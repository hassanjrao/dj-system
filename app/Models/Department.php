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
        return $this->hasMany(User::class);
    }

    public function deliverables()
    {
        return $this->hasMany(Deliverable::class);
    }

    public function musicTypeCompletionDays()
    {
        return $this->hasMany(MusicTypeCompletionDay::class);
    }
}
