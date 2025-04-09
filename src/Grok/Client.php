<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\Grok;

use AIAccess;
use AIAccess\Http;


/**
 * Client implementation for accessing Grok (xAI) API models.
 */
final class Client implements AIAccess\Client
{
	/** @var array<string, mixed> */
	private array $options = [];
	private string $baseUrl = 'https://api.x.ai/v1/';


	public function __construct(
		private string $apiKey,
		private Http\Client $httpClient = new Http\CurlClient,
	) {
	}


	/**
	 * Creates a new Grok chat session instance.
	 */
	public function createChat(string $model): Chat
	{
		return new Chat($this, $model);
	}


	/**
	 * Sets or updates client-wide options.
	 * @param  ?int  $connectTimeout Connection timeout in seconds. Null leaves current setting unchanged.
	 * @param  ?int  $requestTimeout Total request timeout in seconds. Null leaves current setting unchanged.
	 * @param  ?string  $proxy Proxy server URL. Null leaves current setting unchanged.
	 * @param  ?string  $customBaseUrl Override the base API URL. Null leaves current setting unchanged.
	 */
	public function setOptions(
		?int $connectTimeout = null,
		?int $requestTimeout = null,
		?string $proxy = null,
		?string $customBaseUrl = null,
	): static
	{
		$this->options = array_merge($this->options, array_filter(
			compact('connectTimeout', 'requestTimeout', 'proxy'),
			fn($value) => $value !== null,
		));

		if ($customBaseUrl !== null) {
			$this->baseUrl = rtrim($customBaseUrl, '/') . '/';
		}
		return $this;
	}


	/**
	 * Internal method to execute API requests via the injected HTTP client.
	 * Handles authentication, JSON encoding/decoding, and basic error handling for Grok.
	 * @internal
	 * @throws AIAccess\ApiException  On API errors.
	 * @throws AIAccess\NetworkException  On connection problems.
	 * @throws \JsonException  On JSON encode/decode errors.
	 */
	public function sendRequest(
		string $endpoint,
		array|string|null $payload,
		string $method = 'POST',
		array $additionalHeaders = [],
	): mixed
	{
		$url = str_starts_with($endpoint, 'http')
			? $endpoint
			: $this->baseUrl . ltrim($endpoint, '/');

		$body = null;
		$headers = [
			'Authorization' => 'Bearer ' . $this->apiKey,
			'Accept' => 'application/json',
			'User-Agent' => 'ai-access-php/0.1-dev',
		];

		if (is_array($payload)) {
			$body = json_encode($payload, JSON_THROW_ON_ERROR);
			if ($method !== 'GET' && $method !== 'DELETE') {
				$headers['Content-Type'] = 'application/json';
			}
		} elseif (is_string($payload)) {
			// Assuming string payload is pre-formatted JSON or other format where Content-Type is set in $additionalHeaders
			$body = $payload;
		}

		$headers = array_merge($headers, $additionalHeaders);

		$httpResponse = $this->httpClient->request($method, $url, $headers, $body, $this->options);
		$statusCode = $httpResponse->getStatusCode();
		$responseBody = $httpResponse->getBody();
		$contentType = $httpResponse->getHeader('content-type');

		$decodedBody = null;
		// Try to decode if content type is JSON or if it's an error response
		if (
			$responseBody !== '' &&
			(
				(is_string($contentType) && str_contains(strtolower($contentType), 'application/json')) ||
				$statusCode >= 400
			)
		) {
			try {
				$decodedBody = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
			} catch (\JsonException $e) {
				if ($statusCode < 400) {
					return $responseBody; // Return raw body for non-JSON success
				} else {
					// Include raw body in exception message for errors
					throw new AIAccess\ApiException('Invalid JSON response from Grok API: ' . $e->getMessage() . "\nRaw Body: " . $responseBody, $statusCode, $e);
				}
			}
		} elseif ($statusCode < 400) {
			return $responseBody === '' ? null : $responseBody; // Handle non-JSON success
		}

		// Error Handling
		if ($statusCode >= 400) {
			$errorMessage = "Grok API error (HTTP {$statusCode})";
			// Try common error structures (similar to OpenAI)
			if (is_array($decodedBody) && isset($decodedBody['error']['message'])) {
				$errorMessage = $decodedBody['error']['message'];
				if (isset($decodedBody['error']['type'])) {
					$errorMessage .= " (Type: {$decodedBody['error']['type']})";
				}
			} elseif (is_array($decodedBody) && isset($decodedBody['detail'])) { // Another possible format
				if (is_array($decodedBody['detail'])) {
					$errorMessage = json_encode($decodedBody['detail']);
				} else {
					$errorMessage = (string) $decodedBody['detail'];
				}
			} elseif (is_string($responseBody) && $responseBody !== '') {
				$errorMessage = $responseBody; // Fallback to raw body
			}
			throw new AIAccess\ApiException($errorMessage, $statusCode);
		}

		return $decodedBody;
	}
}
