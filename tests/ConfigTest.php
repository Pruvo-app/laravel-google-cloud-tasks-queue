<?php

namespace Tests;

use Pruvo\LaravelGoogleCloudTasksQueue\Exceptions\InvalidCloudTaskConfig;
use Tests\Support\SimpleJob;

class ConfigTest extends TestCase
{
    /** @test */
    public function project_is_required()
    {
        $this->setConfigValue('project', '');

        $this->expectException(InvalidCloudTaskConfig::class);
        $this->expectExceptionCode(InvalidCloudTaskConfig::CODE_GOOGLE_PROJECT_IS_MISSING);

        SimpleJob::dispatch();
    }

    /** @test */
    public function location_is_required()
    {
        $this->setConfigValue('location', '');

        $this->expectException(InvalidCloudTaskConfig::class);
        $this->expectExceptionCode(InvalidCloudTaskConfig::CODE_GOOGLE_LOCATION_IS_MISSING);

        SimpleJob::dispatch();
    }

    /** @test */
    public function service_email_is_required()
    {
        $this->setConfigValue('service_account_email', '');

        $this->expectException(InvalidCloudTaskConfig::class);
        $this->expectExceptionCode(InvalidCloudTaskConfig::CODE_GOOGLE_SERVICE_ACCOUNT_EMAIL_IS_MISSING);

        SimpleJob::dispatch();
    }
}
