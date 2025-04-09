<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\DeepSeek;

use AIAccess;
use AIAccess\Http;


/**
 * Client implementation for accessing DeepSeek API models.
 */
final class Client implements AIAccess\Client
{
	/** @var array<string, mixed> */
	private array $options = [];
	private string $baseUrl = 'https://api.deepseek.com/';


	public function __construct(
		private string $apiKey,
		private Http\Client $httpClient = new Http\CurlClient,
	) {
	}


	/**
	 * Creates a new DeepSeek chat session instance.
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
	 * Handles authentication, JSON encoding/decoding, and basic error handling for DeepSeek.
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
			$body = $payload;
			// Guess content type for string payload if not provided
			if (!isset($additionalHeaders['Content-Type'])) {
				$json = json_decode($payload);
				if ($json !== null && json_last_error() === JSON_ERROR_NONE) {
					$headers['Content-Type'] = 'application/json';
				} else {
					$headers['Content-Type'] = 'text/plain'; // Default guess
				}
			}
		}

		$headers = array_merge($headers, $additionalHeaders);

		$httpResponse = $this->httpClient->request($method, $url, $headers, $body, $this->options);
		$statusCode = $httpResponse->getStatusCode();
		$responseBody = $httpResponse->getBody();
		$contentType = $httpResponse->getHeader('content-type');

		$decodedBody = null;
		// Only attempt JSON decode if content type suggests it or if it's a typical error status code
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
				if ($statusCode < 400) { // Don't throw for non-JSON success response
					return $responseBody; // Return raw body if success but not JSON
				} else { // For errors, try to include raw body in exception
					throw new AIAccess\ApiException('Invalid JSON response from DeepSeek API: ' . $e->getMessage() . "\nRaw Body: " . $responseBody, $statusCode, $e);
				}
			}
		} elseif ($statusCode < 400) {
			// Handle non-JSON success response (e.g., empty body, plain text)
			return $responseBody === '' ? null : $responseBody;
		}


		if ($statusCode >= 400) {
			$errorMessage = "DeepSeek API error (HTTP {$statusCode})";
			if (is_array($decodedBody) && isset($decodedBody['error']['message'])) {
				$errorMessage = $decodedBody['error']['message'];
			} elseif (
				isset($decodedBody['detail'])
				&& is_string($decodedBody['detail'])
			) { // Another possible error format
				$errorMessage = $decodedBody['detail'];
			} elseif (isset($decodedBody['msg']) && is_string($decodedBody['msg'])) { // Yet another format
				$errorMessage = $decodedBody['msg'];
			} elseif (is_string($responseBody) && $responseBody !== '') {
				$errorMessage = $responseBody; // Use raw body if decoding failed or no message found
			}
			throw new AIAccess\ApiException($errorMessage, $statusCode);
		}

		return $decodedBody;
	}
}
