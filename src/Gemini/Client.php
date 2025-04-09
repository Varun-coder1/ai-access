<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\Gemini;

use AIAccess;
use AIAccess\Http;


/**
 * Client implementation for accessing Google Gemini API models.
 */
final class Client implements AIAccess\Client
{
	/** @var array<string, mixed> */
	private array $options = [];
	private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/';


	public function __construct(
		private string $apiKey,
		private Http\Client $httpClient = new Http\CurlClient,
	) {
	}


	/**
	 * Creates a new Gemini chat session instance.
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
	 * Handles authentication, JSON encoding/decoding, and basic error handling.
	 * @internal
	 * @throws AIAccess\ApiException On API errors.
	 * @throws AIAccess\NetworkException On connection problems.
	 * @throws \JsonException On JSON encode/decode errors.
	 */
	public function sendRequest(string $endpoint, array $payload, string $method = 'POST'): mixed
	{
		$url = $this->baseUrl . $endpoint . '?key=' . $this->apiKey;
		$body = json_encode($payload, JSON_THROW_ON_ERROR);
		$headers = [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			'User-Agent' => 'ai-access-php/0.1-dev',
		];

		$httpResponse = $this->httpClient->request($method, $url, $headers, $body, $this->options);
		$statusCode = $httpResponse->getStatusCode();
		$responseBody = $httpResponse->getBody();

		if ($statusCode >= 400) {
			$errorData = json_decode($responseBody, true);
			$errorMessage = $errorData['error']['message'] ?? // Standard Gemini error structure
				(is_string($errorData) ? $errorData : // Sometimes just a string error
				(is_string($responseBody) && $responseBody !== '' ? $responseBody : // Use raw body if not JSON
				"Gemini API error (HTTP {$statusCode})")); // Generic fallback

			throw new AIAccess\ApiException($errorMessage, $statusCode);
		}

		try {
			$data = json_decode($responseBody, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
		} catch (\JsonException $e) {
			throw new AIAccess\ApiException('Invalid JSON response from Claude API: ' . $e->getMessage(), $statusCode, $e);
		}

		if (!is_array($data)) {
			throw new AIAccess\ApiException('Invalid response data');
		}

		return $data;
	}
}
