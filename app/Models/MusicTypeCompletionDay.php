<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusicTypeCompletionDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'music_type_id',
        'department_id',
        'days_before_release',
    ];

    public function musicType()
    {
        return $this->belongsTo(MusicType::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
