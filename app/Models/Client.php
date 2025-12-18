<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'notes',
    ];

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function albums()
    {
        return $this->hasMany(Album::class);
    }
}
