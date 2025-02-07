<?php

namespace App\Jobs;

use App\Models\ScheduledTask;
use App\Models\ScheduledTaskExecution;
use App\Models\Server;
use App\Models\Application;
use App\Models\Service;
use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Throwable;

class ScheduledTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?Team $team = null;
    public Server $server;
    public ScheduledTask $task;
    public Application|Service $resource;

    public ?ScheduledTaskExecution $task_log = null;
    public string $task_status = 'failed';
    public ?string $task_output = null;
    public array $containers = [];

    public function __construct($task)
    {
        $this->task = $task;
        if ($service = $task->service()->first()) {
            $this->resource = $service;
        } else if ($application = $task->application()->first()) {
            $this->resource = $application;
        } else {
            throw new \Exception('ScheduledTaskJob failed: No resource found.');
        }
        $this->team = Team::find($task->team_id);
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->task->id)];
    }

    public function uniqueId(): int
    {
        return $this->task->id;
    }

    public function handle(): void
    {
        try {
            $this->task_log = ScheduledTaskExecution::create([
                'scheduled_task_id' => $this->task->id,
            ]);

            $this->server = $this->resource->destination->server;

            if ($this->resource->type() == 'application') {
                $containers = getCurrentApplicationContainerStatus($this->server, $this->resource->id, 0);
                if ($containers->count() > 0) {
                    $containers->each(function ($container) {
                        $this->containers[] = str_replace('/', '', $container['Names']);
                    });
                }
            }
            elseif ($this->resource->type() == 'service') {
                $this->resource->applications()->get()->each(function ($application) {
                    if (str(data_get($application, 'status'))->contains('running')) {
                        $this->containers[] = data_get($application, 'name') . '-' . data_get($this->resource, 'uuid');
                    }
                });
            }

            if (count($this->containers) == 0) {
                throw new \Exception('ScheduledTaskJob failed: No containers running.');
            }

            if (count($this->containers) > 1 && empty($this->task->container)) {
                throw new \Exception('ScheduledTaskJob failed: More than one container exists but no container name was provided.');
            }

            foreach ($this->containers as $containerName) {
                if (count($this->containers) == 1 || str_starts_with($containerName, $this->task->container . '-' . $this->resource->uuid)) {
                    $cmd = 'sh -c "' . str_replace('"', '\"', $this->task->command)  . '"';
                    $exec = "docker exec {$containerName} {$cmd}";
                    $this->task_output = instant_remote_process([$exec], $this->server, true);
                    $this->task_log->update([
                        'status' => 'success',
                        'message' => $this->task_output,
                    ]);
                    return;
                }
            }

            // No valid container was found.
            throw new \Exception('ScheduledTaskJob failed: No valid container was found. Is the container name correct?');

        } catch (\Throwable $e) {
            if ($this->task_log) {
                $this->task_log->update([
                    'status' => 'failed',
                    'message' => $this->task_output ?? $e->getMessage(),
                ]);
            }
            // send_internal_notification('ScheduledTaskJob failed with: ' . $e->getMessage());
            throw $e;
        }
    }
}
