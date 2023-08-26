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
    protected $signature = 'app:run-deployer-command';

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
                    $filesystem = new Filesystem(new SftpAdapter(
                        new SftpConnectionProvider(
                            $deployment->repository->server->host, // host (required)
                            $deployment->repository->server->username, // username (required)
                            $deployment->repository->server->password, // password (optional, default: null) set to null if privateKey is used
                            null, // private key (optional, default: null) can be used instead of password, set to null if password is set
                            null, // passphrase (optional, default: null), set to null if privateKey is not used or has no passphrase
                            $deployment->repository->server->port, // port (optional, default: 22)
                            true, // use agent (optional, default: false)
                            30, // timeout (optional, default: 10)
                            10, // max tries (optional, default: 4)
                            null, // host fingerprint (optional, default: null),
                            null, // connectivity checker (must be an implementation of 'League\Flysystem\PhpseclibV2\ConnectivityChecker' to check if a connection can be established (optional, omit if you don't need some special handling for setting reliable connections)
                        ),
                        '/upload', // root path (required)
                        PortableVisibilityConverter::fromArray([
                            'file' => [
                                'public' => 0640,
                                'private' => 0604,
                            ],
                            'dir' => [
                                'public' => 0740,
                                'private' => 7604,
                            ],
                        ])
                    ));
                    dd($filesystem->listContents(''));

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
