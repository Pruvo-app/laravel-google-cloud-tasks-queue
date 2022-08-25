<?php

namespace Pruvo\LaravelGoogleCloudTasksQueue\Exceptions;

use Exception;

class InvalidCloudTaskConfig extends Exception
{
    const CODE_UNDEFINED = 0;
    const CODE_GOOGLE_PROJECT_IS_MISSING = 1;
    const CODE_GOOGLE_LOCATION_IS_MISSING = 2;
    const CODE_GOOGLE_SERVICE_ACCOUNT_EMAIL_IS_MISSING = 3;
}
