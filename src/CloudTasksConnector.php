<?php

namespace Pruvo\LaravelGoogleCloudTasksQueue;

use Google\Cloud\Tasks\V2\CloudTasksClient;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Pruvo\LaravelGoogleCloudTasksQueue\Exceptions\InvalidCloudTaskConfig;

class CloudTasksConnector implements ConnectorInterface
{
    public function connect(array $config)
    {
        $this->validate($config);

        // The handler is the URL which Cloud Tasks will call with the job payload. This
        // URL of the handler can be manually set through an environment variable, but
        // if it is not then we will choose a sensible default (the current app url)
        if (empty($config['handler'])) {
            // At this point (during service provider boot) the trusted proxy middleware
            // has not been set up, and so we are not ready to get the scheme and host
            // So we wrap it and get it later, after the middleware has been set up.
            $config['handler'] = function () {
                return request()->getSchemeAndHttpHost() . config('cloud-tasks.handler.uri');
            };
        }

        return new CloudTasksQueue($config, app(CloudTasksClient::class));
    }

    private function validate(array $config)
    {
        if (empty($config['project'])) {
            throw new InvalidCloudTaskConfig(
                'Google Cloud project not provided. To fix this, set the CLOUD_TASKS_PROJECT environment variable',
                InvalidCloudTaskConfig::CODE_GOOGLE_PROJECT_IS_MISSING
            );
        }

        if (empty($config['location'])) {
            throw new InvalidCloudTaskConfig(
                'Google Cloud Tasks location not provided. To fix this, set the CLOUD_TASKS_LOCATION environment variable',
                InvalidCloudTaskConfig::CODE_GOOGLE_LOCATION_IS_MISSING
            );
        }

        if (empty($config['service_account_email'])) {
            throw new InvalidCloudTaskConfig(
                'Google Service Account email address not provided. This is needed to secure the handler so it is only accessible by Google.'
                    . 'To fix this, set the CLOUD_TASKS_SERVICE_EMAIL environment variable',
                InvalidCloudTaskConfig::CODE_GOOGLE_SERVICE_ACCOUNT_EMAIL_IS_MISSING
            );
        }
    }
}
