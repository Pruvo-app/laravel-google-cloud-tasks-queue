<?php

namespace Pruvo\LaravelGoogleCloudTasksQueue\Exceptions;

use Exception;

class InvalidHandlerEndpointException extends Exception
{
    const CODE_UNDEFINED = 0;
    const CODE_INVALID_HOST = 1;
    const CODE_IS_LOCALHOST = 2;
    const CODE_MUST_NOT_CONTAINS_PATH = 3;
    const CODE_INVALID_HANDLER_URL = 4;
}
