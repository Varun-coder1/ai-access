<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\OpenAI;

use AIAccess;
use AIAccess\Http;


/**
 * Client implementation for accessing OpenAI API models.
 */
final class Client implements AIAccess\Client, AIAccess\EmbeddingFeature
{
	/** @var array<string, mixed> */
	private array $options = [];
	private string $baseUrl = 'https://api.openai.com/v1/';
	private ?string $organizationId = null;


	public function __construct(
		private string $apiKey,
		private Http\Client $httpClient = new Http\CurlClient,
	) {
	}


	/**
	 * Creates a new OpenAI chat session instance.
	 */
	public function createChat(string $model): Chat
	{
		return new Chat($this, $model);
	}


	/**
	 * Calculates embeddings for the given input text(s) using a specified OpenAI model.
	 * @param  ?int  $dimensions  The number of dimensions the resulting output embeddings should have. Only supported for 'text-embedding-3' models
	 * @return AIAccess\Embedding[]
	 * @throws AIAccess\Exception
	 */
	public function calculateEmbeddings(string $model, array $input, ?int $dimensions = null): array
	{
		if (empty($input)) {
			throw new AIAccess\LogicException('Input cannot be empty.');
		}
		foreach ($input as $text) {
			if (!is_string($text) || $text === '') {
				throw new AIAccess\LogicException('All input elements must be non-empty strings.');
			}
		}

		$payload = [
			'model' => $model,
			'input' => $input,
		];
		if ($dimensions !== null) {
			if (!str_contains($model, 'text-embedding-3')) {
				trigger_error("The 'dimensions' parameter is only supported for text-embedding-3 models.", E_USER_WARNING);
			}
			$payload['dimensions'] = $dimensions;
		}

		$response = $this->sendRequest('embeddings', $payload);

		$results = [];
		if (isset($response['data']) && is_array($response['data'])) {
			usort($response['data'], fn($a, $b) => $a['index'] <=> $b['index']);

			foreach ($response['data'] as $data) {
				if (is_array($data['embedding'] ?? null)) {
					$results[] = new AIAccess\Embedding($data['embedding']);
				} elseif (isset($data['error'])) {
					trigger_error("Error processing input at index {$data['index']}: " . ($data['error']['message'] ?? 'Unknown error'), E_USER_WARNING);
				}
			}
		}

		if (count($results) !== count($input)) {
			trigger_error('Number of returned embeddings (' . count($results) . ') does not match the number of inputs (' . count($input) . '). Check for errors in the raw response.', E_USER_WARNING);
		}

		return $results;
	}


	/**
	 * Sets or updates client-wide options.
	 * @param  ?int  $connectTimeout  Connection timeout in seconds. Null leaves current setting unchanged.
	 * @param  ?int  $requestTimeout  Total request timeout in seconds. Null leaves current setting unchanged.
	 * @param  ?string  $proxy  Proxy server URL. Null leaves current setting unchanged.
	 * @param  ?string  $customBaseUrl  Override the base API URL. Null leaves current setting unchanged.
	 * @param  ?string  $organizationId  Set the OpenAI Organization ID. Null leaves current setting unchanged or removes it.
	 */
	public function setOptions(
		?int $connectTimeout = null,
		?int $requestTimeout = null,
		?string $proxy = null,
		?string $customBaseUrl = null,
		?string $organizationId = null,
	): static
	{
		$this->options = array_merge($this->options, array_filter(
			compact('connectTimeout', 'requestTimeout', 'proxy'),
			fn($value) => $value !== null,
		));

		if ($customBaseUrl !== null) {
			$this->baseUrl = rtrim($customBaseUrl, '/') . '/';
		}
		if ($organizationId !== null) {
			$this->organizationId = $organizationId;
		}
		return $this;
	}


	/**
	 * Internal method to execute API requests via the injected HTTP client.
	 * Handles authentication, JSON encoding/decoding, and basic error handling for OpenAI.
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
		$url = $this->baseUrl . ltrim($endpoint, '/');
		$body = null;
		$headers = [
			'Authorization' => 'Bearer ' . $this->apiKey,
			'User-Agent' => 'ai-access-php/0.1-dev',
		];

		if (is_array($payload)) {
			$body = json_encode($payload, JSON_THROW_ON_ERROR);
			if ($method !== 'GET' && $method !== 'DELETE') {
				$headers['Content-Type'] = 'application/json';
			}
		} elseif (is_string($payload)) {
			$body = $payload;
		}

		if ($this->organizationId !== null) {
			$headers['OpenAI-Organization'] = $this->organizationId;
		}

		$headers = array_merge($headers, $additionalHeaders);

		$httpResponse = $this->httpClient->request($method, $url, $headers, $body, $this->options);
		$statusCode = $httpResponse->getStatusCode();
		$responseBody = $httpResponse->getBody();

		$decodedBody = null;
		if ($statusCode < 400 && $responseBody !== '') {
			$decodedBody = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
		}

		if ($statusCode >= 400) {
			$errorData = is_array($decodedBody) ? $decodedBody : null;
			$errorMessage = "OpenAI API error (HTTP {$statusCode})";
			if (is_array($errorData) && isset($errorData['error']['message'])) {
				$errorMessage = $errorData['error']['message'];
			} elseif (is_string($responseBody) && $responseBody !== '') {
				$errorMessage = $responseBody;
			}
			throw new AIAccess\ApiException($errorMessage, $statusCode);
		}

		return $decodedBody ?? ($responseBody === '' ? null : $responseBody);
	}
}
