<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\Http;

use AIAccess\NetworkException;


/**
 * Interface for sending HTTP requests required by the AI Access library.
 */
interface Client
{
	/** Default connection timeout in seconds */
	public const DefaultConnectTimeout = 10;

	/** Default total request timeout in seconds */
	public const DefaultRequestTimeout = 60;

	/**
	 * Sends an HTTP request.
	 * @param  array<string, mixed>  $options Additional transport options (e.g., 'connectTimeout', 'requestTimeout').
	 * @throws NetworkException On connection errors, timeouts, etc.
	 */
	function request(
		string $method,
		string $url,
		array $headers = [],
		?string $body = null,
		array $options = [],
	): Response;
}
