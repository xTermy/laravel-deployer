<?php

namespace App\Models;

use App\Enums\DeploymentStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    protected $fillable = [
        'repository_id',
        'code',
        'head_commit_id',
        'committer',
        'last_command_id',
        'status',
    ];

    protected $casts = [
        'repository_id' => 'integer',
        'last_command_id' => 'integer',
        'status' => DeploymentStatusEnum::class,
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

    public function lastCommand()
    {
        return $this->belongsTo(Command::class, 'last_command_id', 'id');
    }
}
