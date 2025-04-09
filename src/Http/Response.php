<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\Http;


/**
 * Represents an HTTP response received from a server.
 */
interface Response
{
	/**
	 * Gets the HTTP status code.
	 */
	function getStatusCode(): int;

	/**
	 * Gets the response body as a string.
	 */
	function getBody(): string;

	/**
	 * Gets a specific response header's value(s) using case-insensitive lookup.
	 */
	function getHeader(string $name): string|array|null;

	/**
	 * Gets all response headers. Header names are typically normalized to lowercase.
	 * @return array<string, string|string[]>
	 */
	function getHeaders(): array;
}
