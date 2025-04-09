<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\Http;

use AIAccess\NetworkException;


/**
 * cURL-based implementation of the HTTP Client interface.
 * @internal
 */
final class CurlClient implements Client
{
	public function request(
		string $method,
		string $url,
		array $headers = [],
		?string $body = null,
		array $options = [],
	): Response
	{
		$ch = curl_init();
		if ($ch === false) {
			throw new NetworkException('Failed to initialize cURL session.');
		}

		$curlHeaders = [];
		foreach ($headers as $key => $value) {
			foreach ((array) $value as $v) {
				$curlHeaders[] = $key . ': ' . $v;
			}
		}

		$connectTimeout = $options['connectTimeout'] ?? self::DefaultConnectTimeout;
		$requestTimeout = $options['requestTimeout'] ?? self::DefaultRequestTimeout;

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $connectTimeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, max((int) $connectTimeout, (int) $requestTimeout));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		if ($body !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		if (isset($options['proxy'])) {
			curl_setopt($ch, CURLOPT_PROXY, $options['proxy']);
		}

		try {
			$rawResponse = curl_exec($ch);

			if ($rawResponse === false) {
				$errorNo = curl_errno($ch);
				$errorMsg = curl_error($ch);
				throw new NetworkException('cURL request failed: [' . $errorNo . '] ' . $errorMsg, $errorNo);
			}

			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$rawHeaders = substr($rawResponse, 0, $headerSize);
			$responseBody = substr($rawResponse, $headerSize);
			$responseHeaders = $this->parseHeaders($rawHeaders);

			return new CurlResponse($httpCode, $responseBody, $responseHeaders);

		} finally {
			curl_close($ch); // Ensure cURL handle is always closed
		}
	}


	/**
	 * Parses raw HTTP headers into an associative array.
	 * Normalizes header names to lowercase.
	 * @return array<string, string|string[]>
	 */
	private function parseHeaders(string $rawHeaders): array
	{
		$headers = [];
		$lines = explode("\r\n", trim($rawHeaders));
		array_shift($lines); // Skip the first line (HTTP status line)

		foreach ($lines as $line) {
			if (!str_contains($line, ':')) {
				continue; // Skip lines without a colon (e.g., empty lines)
			}

			[$name, $value] = explode(':', $line, 2);
			$name = strtolower(trim($name));
			$value = trim($value);

			if (isset($headers[$name])) {
				// Handle multiple headers with the same name (e.g., Set-Cookie)
				if (!is_array($headers[$name])) {
					$headers[$name] = [$headers[$name]]; // Convert to array
				}
				$headers[$name][] = $value;
			} else {
				$headers[$name] = $value; // Store as string initially
			}
		}

		return $headers;
	}
}
