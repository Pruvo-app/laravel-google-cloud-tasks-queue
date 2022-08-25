<?php

namespace Pruvo\LaravelGoogleCloudTasksQueue;

use Google\Cloud\Tasks\V2\CloudTasksClient;
use Illuminate\Queue\QueueManager;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class CloudTasksServiceProvider extends ServiceProvider
{
    public function boot(QueueManager $queue, Router $router)
    {
        $this->registerClient();
        $this->registerConnector($queue);
        $this->registerConfig();
        $this->registerRoutes($router);
    }

    private function registerClient()
    {
        $this->app->singleton(CloudTasksClient::class, function () {
            return new CloudTasksClient();
        });
    }

    private function registerConnector(QueueManager $queue)
    {
        $queue->addConnector('cloudtasks', function () {
            return new CloudTasksConnector;
        });
    }

    private function registerConfig(): void
    {
        $this->publishes(
            [__DIR__ . '/../config/cloud-tasks.php' => config_path('cloud-tasks.php')],
            ['cloud-tasks']
        );

        $this->mergeConfigFrom(__DIR__ . '/../config/cloud-tasks.php', 'cloud-tasks');
    }

    private function registerRoutes(Router $router)
    {
        $router
            ->post(config('cloud-tasks.handler.uri'), [config('cloud-tasks.handler.controller'), 'handle'])
            ->name(config('cloud-tasks.handler.name'));
    }
}
