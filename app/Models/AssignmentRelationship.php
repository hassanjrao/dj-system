<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_assignment_id',
        'child_assignment_id',
    ];

    public function parentAssignment()
    {
        return $this->belongsTo(Assignment::class, 'parent_assignment_id');
    }

    public function childAssignment()
    {
        return $this->belongsTo(Assignment::class, 'child_assignment_id');
    }
}
