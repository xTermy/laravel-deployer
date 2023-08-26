<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    protected $fillable = [
        'repository_name',
        'git_url',
        'prod_branch',
        'prod_server_id',
        'dev_branch',
        'dev_server_id',
    ];

    public function prodServer()
    {
        return $this->belongsTo(Server::class, 'prod_server_id', 'id');
    }

    public function devServer()
    {
        return $this->belongsTo(Server::class, 'dev_server_id', 'id');
    }
}
