<?php

namespace App\Console\Commands;

use App\Enums\DeploymentStatusEnum;
use App\Models\Deployment;
use App\Services\DeploymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

class RunDeployerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-deployer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deploymentService = new DeploymentService();
        if(!Storage::exists('deployments')) {
            Storage::makeDirectory('deployments');
        }
        Process::run('git config --global advice.detachedHead false');
        while(true) {
            foreach(Deployment::with(['lastCommand', 'repository', 'repository.commands', 'repository.server'])->where('status', DeploymentStatusEnum::Awaiting)->get() as $deployment) {
                try {
                    if(!Storage::exists('deployments/'.explode('/', strtolower($deployment->repository->repository_name))[0])) {
                        Storage::makeDirectory('deployments/'.explode('/', strtolower($deployment->repository->repository_name))[0]);
                    }
                    if(!Storage::exists('deployments/'.strtolower($deployment->repository->repository_name))) {
                        Storage::makeDirectory('deployments/'.strtolower($deployment->repository->repository_name));
                    }
                    if(Storage::exists('deployments/'.strtolower($deployment->repository->repository_name).'/'.$deployment->code)) {
                        Storage::deleteDirectory('deployments/'.strtolower($deployment->repository->repository_name).'/'.$deployment->code);
                    }
                    //set status
                    $process = Process::run('cd '.Storage::path('deployments/'.strtolower($deployment->repository->repository_name)).' && git clone  -b '. $deployment->repository->branch .' --single-branch '. $deployment->repository->git_url .' '.$deployment->code . ' && cd '. $deployment->code .' && git checkout '.$deployment->head_commit_id.' && rm -rf .git');

                    if($process->exitCode() !== 0) {
                        Log::log('error', $process->errorOutput());
                        //set status
                        throw new \Exception($process->errorOutput());
                    }
                    Log::log('info', $process->output());

                    foreach($deployment->repository->commands as $command) {
                        $deployment->update(['last_command_id', $command->id]);
                        $process = Process::run('. ~/.bashrc && cd '.Storage::path('deployments/'.strtolower($deployment->repository->repository_name)).'/'.$deployment->code.' && '.$command->command);

                        if($process->exitCode() > 0) {
                            Log::log('error', $process->errorOutput());
                            //set status
                            throw new \Exception($process->errorOutput());
                        }
                        Log::log('info', $process->output());
                    }
                    $process = Process::run('sshpass -p \''.$deployment->repository->server->password.'\' ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -p '.$deployment->repository->server->port.' '.$deployment->repository->server->username.'@'.$deployment->repository->server->host .' \'rm -rf '.$deployment->repository->server->path.'/*\'');
                    if($process->exitCode() > 0) {
                        Log::log('error', $process->errorOutput());
                        //set status
                        throw new \Exception($process->errorOutput());
                    }
                    Log::log('info', $process->output());

                    $process = Process::run('echo "put -r '.Storage::path('deployments/'.strtolower($deployment->repository->repository_name).'/'.$deployment->code).'/*" | sshpass -p \''.$deployment->repository->server->password.'\' sftp -oBatchMode=no -P '.$deployment->repository->server->port.' '.$deployment->repository->server->username.'@'.$deployment->repository->server->host.':'.$deployment->repository->server->path);
                    if($process->exitCode() > 0) {
                        Log::log('error', $process->errorOutput());
                        //set status
                        throw new \Exception($process->errorOutput());
                    }
                    Log::log('info', $process->output());

                    $process = Process::run('echo "put -r '.Storage::path('deployments/'.strtolower($deployment->repository->repository_name).'/'.$deployment->code).'/.*" | sshpass -p \''.$deployment->repository->server->password.'\' sftp -oBatchMode=no -P '.$deployment->repository->server->port.' '.$deployment->repository->server->username.'@'.$deployment->repository->server->host.':'.$deployment->repository->server->path);
                    if($process->exitCode() > 0) {
                        Log::log('error', $process->errorOutput());
                        //set status
                        throw new \Exception($process->errorOutput());
                    }
                    Log::log('info', $process->output());
//                    dump($deployment);
                    exit();
                } catch (\Throwable $th) {
                    dd($th);
                }
            }
            sleep(5);
        }
    }
}
