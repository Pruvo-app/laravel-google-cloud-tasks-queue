<?php

use Pruvo\LaravelGoogleCloudTasksQueue\TaskHandler;

return [
    'handler'=> [
        'uri'=> env('CLOUD_TASKS_URI', '/handle-task'),
        'controller'=> TaskHandler::class,
        'name'=> 'cloud-tasks.handle-task',
    ],
];
