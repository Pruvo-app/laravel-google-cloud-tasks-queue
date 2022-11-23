<?php

namespace Pruvo\LaravelGoogleCloudTasksQueue;

use Google\Cloud\Tasks\V2\CloudTasksClient;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\HttpRequest;
use Google\Cloud\Tasks\V2\OidcToken;
use Google\Cloud\Tasks\V2\Queue;
use Google\Cloud\Tasks\V2\Task;
use Google\Protobuf\Timestamp;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue as LaravelQueue;
use Illuminate\Support\InteractsWithTime;

class CloudTasksQueue extends LaravelQueue implements QueueContract
{
    use InteractsWithTime;

    private $client;
    private $default;
    private $config;

    public function __construct(array $config, CloudTasksClient $client)
    {
        $this->client = $client;
        $this->default = $config['queue'];
        $this->config = $config;
    }

    public function size($queue = null)
    {
        // TODO: Implement size() method.
    }

    public function push($job, $data = '', $queue = null)
    {
        return $this->pushToCloudTasks($queue, $this->createPayload(
            $job,
            $this->getQueue($queue),
            $data
        ));
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->pushToCloudTasks($queue, $payload);
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->pushToCloudTasks($queue, $this->createPayload(
            $job,
            $this->getQueue($queue),
            $data
        ), $delay);
    }

    protected function pushToCloudTasks($queue, $payload, $delay = 0, $attempts = 0)
    {
        $queue = $this->getQueue($queue);
        $queueName = $this->client->queueName($this->config['project'], $this->config['location'], $queue);
        $availableAt = $this->availableAt($delay);

        $httpRequest = $this->createHttpRequest();
        $httpRequest->setUrl($this->config['handler']);
        $httpRequest->setHttpMethod(HttpMethod::POST);
        $httpRequest->setBody($payload);

        $task = $this->createTask();
        $task->setHttpRequest($httpRequest);

        $token = new OidcToken;
        $token->setServiceAccountEmail($this->config['service_account_email']);
        $httpRequest->setOidcToken($token);

        if ($availableAt > time()) {
            $task->setScheduleTime(new Timestamp(['seconds' => $availableAt]));
        }

        try {
            $this->client->createTask($queueName, $task);
        } catch (\Exception $e) {
            if ($e->getCode() === 9) {
                $formattedParent = $this->client->locationName($this->config['project'], $this->config['location']);
                $this->client->createQueue($formattedParent, new Queue(['name' => $queueName]));
                $this->client->createTask($queueName, $task);
            }
            throw new \Exception("Could not create task on queue $queueName: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function pop($queue = null)
    {
        // TODO: Implement pop() method.
    }

    private function getQueue($queue = null)
    {
        return $queue ?: $this->default;
    }

    /**
     * @return HttpRequest
     */
    private function createHttpRequest()
    {
        return app(HttpRequest::class);
    }

    public function delete(CloudTasksJob $job)
    {
        $config = $this->config;

        $taskName = $this->client->taskName(
            $config['project'],
            $config['location'],
            $job->getQueue(),
            request()->header('X-Cloudtasks-Taskname')
        );

        $this->client->deleteTask($taskName);
    }

    /**
     * @return Task
     */
    private function createTask()
    {
        return app(Task::class);
    }
}
