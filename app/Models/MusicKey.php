<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusicKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'note',
        'is_active',
    ];

    /**
     * Display as "name , note" (e.g. "1A , G#m").
     */
    public function getDisplayNameAttribute(): string
    {
        return trim($this->name . ' , ' . ($this->note ?? ''));
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['display_name'];

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
