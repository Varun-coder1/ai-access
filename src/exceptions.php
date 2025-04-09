<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess;

/**
 * Marker interface for exceptions thrown by the AIAccess library.
 */
interface Exception
{
}

/**
 * This exception indicates that a method has been invoked with improper parameters
 * or that a particular library state is invalid.
 */
class LogicException extends \Exception implements Exception
{
}


/**
 * The AI provider API returned an error.
 */
class ApiException extends \Exception implements Exception
{
}


/**
 * A network error occurred while communicating with the API.
 */
class NetworkException extends \Exception implements Exception
{
}
