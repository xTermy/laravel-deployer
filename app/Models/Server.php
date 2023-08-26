<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $table = 'servers';
    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'path',
    ];

    public function repositories()
    {
        return $this->hasMany(Repository::class, 'server_id', 'id');
    }
}
