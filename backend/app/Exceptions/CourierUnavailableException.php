<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown when the courier API is down or returns an error. The job will retry. */
class CourierUnavailableException extends RuntimeException
{
}
