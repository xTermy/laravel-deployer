<?php

namespace App\Services;

use App\Models\Deployment;

class DeploymentService
{
    public function deploy(Deployment $deployment)
    {
        dd($deployment);
    }
}
