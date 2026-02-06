<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusicType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'music_type_category_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(MusicTypeCategory::class, 'music_type_category_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
