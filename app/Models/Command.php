<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    protected $fillable = [
        'repository_id',
        'command',
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }
}
