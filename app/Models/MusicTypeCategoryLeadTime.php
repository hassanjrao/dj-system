<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusicTypeCategoryLeadTime extends Model
{
    use HasFactory;

    protected $table = 'music_type_category_lead_times';

    protected $fillable = [
        'music_type_category_id',
        'department_id',
        'days_before_release',
    ];

    public function musicTypeCategory()
    {
        return $this->belongsTo(MusicTypeCategory::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
