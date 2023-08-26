<?php

namespace App\Http\Controllers;

use App\Enums\DeploymentStatusEnum;
use App\Models\Deployment;
use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhooksController extends Controller
{
    public function github(Request $request)
    {
        $data = json_decode($request->input('payload'), true);

        $repo = Repository::where('repository_name', $data['repository']['full_name'])->first();
        if(is_null($repo)) {
            return response()->json();
        }
        do {
            $deploymentCode = Str::random(30);
        } while(Deployment::where('code', $deploymentCode)->count() > 0);
        $ref = explode('/', $data['ref']);
        if ($repo->branch === last($ref)) {
            $repo->deployments()->create([
                'code' => $deploymentCode,
                'head_commit_id' => $data['head_commit']['id'],
                'committer' => $data['head_commit']['committer']['name'],
                'last_command_id' => null,
                'status' => DeploymentStatusEnum::Awaiting,
            ]);
        }
        return response()->json();
    }
}
