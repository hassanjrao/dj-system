<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusicTypeCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function musicTypes()
    {
        return $this->hasMany(MusicType::class);
    }
}
