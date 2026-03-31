<?php

declare(strict_types=1);

namespace Sniper\Exceptions;

use RuntimeException;

/**
 * Base exception for all phpSniper errors.
 *
 * All library-specific exceptions extend this class, allowing consumers
 * to catch `SniperException` as a blanket handler for any phpSniper failure.
 */
class SniperException extends RuntimeException
{
}
