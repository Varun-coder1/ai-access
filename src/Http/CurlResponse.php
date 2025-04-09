<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\Http;


/**
 * cURL-based implementation of the HTTP Response interface.
 * @internal
 */
final class CurlResponse implements Response
{
	/**
	 * @param array<string, string|string[]> $headers Assumed to have lowercased keys
	 */
	public function __construct(
		private int $statusCode,
		private string $body,
		private array $headers,
	) {
	}


	public function getStatusCode(): int
	{
		return $this->statusCode;
	}


	public function getBody(): string
	{
		return $this->body;
	}


	/**
	 * Gets a specific response header's value(s) using case-insensitive lookup.
	 * Returns string for single value, array for multi-value, or null if not present.
	 */
	public function getHeader(string $name): string|array|null
	{
		return $this->headers[strtolower($name)] ?? null;
	}


	/**
	 * Gets all parsed response headers. Header names are lowercased.
	 * @return array<string, string|string[]>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}
}
