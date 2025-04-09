<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\Claude;

use AIAccess;
use AIAccess\Http;


/**
 * Client implementation for accessing Anthropic Claude API models.
 */
final class Client implements AIAccess\Client, AIAccess\BatchFeature
{
	/** @var array<string, mixed> */
	private array $options = [];
	private string $baseUrl = 'https://api.anthropic.com/';
	private string $apiVersion = '2023-06-01';


	public function __construct(
		private string $apiKey,
		private Http\Client $httpClient = new Http\CurlClient,
	) {
	}


	/**
	 * Creates a new Claude chat session instance.
	 */
	public function createChat(string $model): Chat
	{
		return new Chat($this, $model);
	}


	/**
	 * Creates a new batch job container.
	 */
	public function createBatch(): Batch
	{
		return new Batch($this);
	}


	/**
	 * Lists existing batch jobs.
	 * @param  ?int  $limit  Maximum number of jobs to return
	 * @param  ?string  $after  Cursor for pagination (retrieve the page after this batch ID)
	 * @param  ?string  $before  Cursor for pagination (retrieve the page before this batch ID)
	 * @return BatchResponse[]
	 */
	public function listBatches(?int $limit = null, ?string $after = null, ?string $before = null): array
	{
		$endpoint = 'v1/messages/batches';
		$params = array_filter([
			'limit' => $limit,
			'after_id' => $after,
			'before_id' => $before,
		], fn($v) => $v !== null);

		if ($params) {
			$endpoint .= '?' . http_build_query($params);
		}

		$response = $this->sendRequest($endpoint, [], 'GET');
		$res = [];
		if (is_array($response['data'] ?? null)) {
			foreach ($response['data'] as $batchData) {
				$res[] = new BatchResponse($this, $batchData);
			}
		}
		return $res;
	}


	/**
	 * Retrieves the current status and details of a specific batch job by its ID.
	 */
	public function retrieveBatch(string $id): BatchResponse
	{
		$rawResponse = $this->sendRequest("v1/messages/batches/{$id}", [], 'GET');
		return new BatchResponse($this, $rawResponse);
	}


	/**
	 * Attempts to cancel a batch job that is currently in progress.
	 * @return bool True if cancellation was initiated successfully, false otherwise
	 */
	public function cancelBatch(string $id): bool
	{
		try {
			$response = $this->sendRequest("v1/messages/batches/{$id}/cancel", []);
			return isset($response['cancel_initiated_at']) && $response['cancel_initiated_at'] !== null;
		} catch (AIAccess\ApiException $e) {
			// Handle case where batch can't be cancelled (e.g., already completed)
			trigger_error("Failed to cancel batch job {$id}: " . $e->getMessage(), E_USER_WARNING);
			return false;
		}
	}


	/**
	 * Sets or updates client-wide options.
	 * @param  ?int  $connectTimeout Connection timeout in seconds. Null leaves current setting unchanged.
	 * @param  ?int  $requestTimeout Total request timeout in seconds. Null leaves current setting unchanged.
	 * @param  ?string  $proxy Proxy server URL. Null leaves current setting unchanged.
	 * @param  ?string  $customBaseUrl Override the base API URL. Null leaves current setting unchanged.
	 * @param  ?string  $apiVersion Override the Anthropic API version. Null leaves current setting unchanged.
	 */
	public function setOptions(
		?int $connectTimeout = null,
		?int $requestTimeout = null,
		?string $proxy = null,
		?string $customBaseUrl = null,
		?string $apiVersion = null,
	): static
	{
		$this->options = array_merge($this->options, array_filter(
			compact('connectTimeout', 'requestTimeout', 'proxy'),
			fn($value) => $value !== null,
		));

		if ($customBaseUrl !== null) {
			$this->baseUrl = rtrim($customBaseUrl, '/') . '/';
		}

		if ($apiVersion !== null) {
			$this->apiVersion = $apiVersion;
		}

		return $this;
	}


	/**
	 * Internal method to execute API requests via the injected HTTP client.
	 * Handles authentication, JSON encoding/decoding, and basic error handling.
	 * @internal
	 * @throws AIAccess\ApiException On API errors.
	 * @throws AIAccess\NetworkException On connection problems.
	 */
	public function sendRequest(
		string $endpoint,
		array $payload,
		string $method = 'POST',
		bool $parseJson = true,
	): mixed
	{
		$url = str_starts_with($endpoint, 'http') ? $endpoint : $this->baseUrl . ltrim($endpoint, '/');
		$body = null;

		if (!empty($payload) && $method !== 'GET') {
			$body = json_encode($payload, JSON_THROW_ON_ERROR);
		}

		$headers = [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			'Anthropic-Version' => $this->apiVersion,
			'x-api-key' => $this->apiKey,
			'User-Agent' => 'ai-access-php/0.1-dev',
		];

		$httpResponse = $this->httpClient->request($method, $url, $headers, $body, $this->options);
		$statusCode = $httpResponse->getStatusCode();
		$responseBody = $httpResponse->getBody();

		if ($statusCode >= 400) {
			$errorData = json_decode($responseBody, true);
			$errorMessage = $errorData['error']['message'] ??
				(is_string($errorData) ? $errorData :
				(is_string($responseBody) && $responseBody !== '' ? $responseBody :
				"Claude API error (HTTP {$statusCode})"));

			throw new AIAccess\ApiException($errorMessage, $statusCode);
		}

		if (!$parseJson) {
			return $responseBody;
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
