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
final class Client implements AIAccess\Client, AIAccess\EmbeddingFeature
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
	 * Calculates embeddings using Gemini models via the batch endpoint.
	 * @param  ?string  $taskType Optional task type hint (e.g., RETRIEVAL_QUERY, RETRIEVAL_DOCUMENT)
	 * @param  ?string  $title Optional title if taskType is RETRIEVAL_DOCUMENT
	 * @param  ?int  $outputDimensionality Optional request for specific embedding dimensions
	 * @return AIAccess\Embedding[]
	 * @throws AIAccess\Exception
	 */
	public function calculateEmbeddings(
		string $model,
		array $input,
		?string $taskType = null,
		?string $title = null,
		?int $outputDimensionality = null,
	): array
	{
		if (empty($input)) {
			return [];
		}

		$requests = [];
		foreach ($input as $text) {
			if (!is_string($text) || $text === '') {
				throw new AIAccess\LogicException('All input elements must be non-empty strings.');
			}
			$content = ['parts' => [['text' => $text]]];
			$request = ['model' => "models/$model", 'content' => $content];

			if ($taskType !== null) {
				$request['taskType'] = $taskType;
			}
			if ($title !== null && $taskType === 'RETRIEVAL_DOCUMENT') {
				$request['title'] = $title;
			}
			if ($outputDimensionality !== null) {
				$request['outputDimensionality'] = $outputDimensionality;
			}

			$requests[] = $request;
		}

		$response = $this->sendRequest("models/{$model}:batchEmbedContents", ['requests' => $requests]);
		$results = [];
		if (isset($response['embeddings']) && is_array($response['embeddings'])) {
			foreach ($response['embeddings'] as $index => $data) {
				if (isset($data['values']) && is_array($data['values'])) {
					$results[$index] = new AIAccess\Embedding($data['values']);
				}
			}
		}

		if (count($results) !== count($input)) {
			trigger_error('Number of returned embeddings does not match the number of inputs.', E_USER_WARNING);
		}
		return $results;
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
