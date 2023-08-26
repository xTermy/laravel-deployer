<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    protected $fillable = [
        'repository_name',
        'git_url',
        'branch',
        'server_id',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id', 'id');
    }

    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }
}
