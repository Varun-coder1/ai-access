<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\OpenAI;

use AIAccess;
use AIAccess\Role;

/**
 * OpenAI implementation of a chat session state container.
 */
final class Chat extends AIAccess\Chat
{
	/** @var array<string, mixed> */
	private array $options = [];


	/**
	 * Sets options specific to this OpenAI chat session.
	 *
	 * @param  ?int  $maxOutputTokens  An upper bound for tokens in the response.
	 * @param  ?float  $temperature  What sampling temperature to use (0–2).
	 * @param  ?float  $topP  Nucleus sampling parameter.
	 * @param  ?string $truncation  Truncation strategy; either "auto" or "disabled".
	 * @param  ?array  $metadata  Optional metadata (map of up to 16 key-value pairs).
	 * @param  ?bool  $parallelToolCalls  Whether to allow parallel tool calls.
	 * @param  ?string $previousResponseId The ID of the previous response for multi-turn conversations.
	 * @param  ?array  $reasoning  Configuration options for reasoning models.
	 * @param  ?bool  $store  Whether to store the generated model response.
	 * @param  ?bool  $stream  If true, the response will be streamed.
	 * @param  ?array  $text  Configuration options for text response formatting.
	 * @param  ?float  $topP2  Alternativně lze předat top_p (totéž jako $topP).
	 * @param  ?array  $include  Specify additional output data to include.
	 * @param  ?array  $tools  An array of tools the model may call.
	 */
	public function setOptions(
		?int $maxOutputTokens = null,
		?float $temperature = null,
		?float $topP = null,
		?string $truncation = null,
		?array $metadata = null,
		?bool $parallelToolCalls = null,
		?string $previousResponseId = null,
		?array $reasoning = null,
		?bool $store = null,
		?bool $stream = null,
		?array $text = null,
		?array $include = null,
		?array $tools = null,
	): static
	{
		$this->options = array_merge($this->options, array_filter(
			[
				'max_output_tokens' => $maxOutputTokens,
				'temperature' => $temperature,
				'top_p' => $topP,
				'truncation' => $truncation,
				'metadata' => $metadata,
				'parallel_tool_calls' => $parallelToolCalls,
				'previous_response_id' => $previousResponseId,
				'reasoning' => $reasoning,
				'store' => $store,
				'stream' => $stream,
				'text' => $text,
				'include' => $include,
				'tools' => $tools,
			],
			fn($value) => $value !== null,
		));
		return $this;
	}


	/**
	 * Sends the next message to the model and returns the model response.
	 */
	protected function generateResponse(): Response
	{
		$rawResponse = $this->client->sendRequest('responses', $this->buildPayload());
		$response = new Response($rawResponse);
		return $response;
	}


	/**
	 * Builds the payload for the OpenAI API responses request.
	 * @internal
	 */
	public function buildPayload(): array
	{
		if (empty($this->messages)) {
			throw new AIAccess\LogicException('Cannot send request with empty message history.');
		}

		$input = [];
		foreach ($this->messages as $message) {
			$role = match ($message->getRole()) {
				Role::User => 'user',
				Role::Model => 'assistant',
			};
			$input[] = [
				'role' => $role,
				'content' => $message->getText(),
			];
		}

		$payload = [
			'model' => $this->model,
			'input' => $input,
		];

		if ($this->systemInstruction !== null) {
			$payload['instructions'] = $this->systemInstruction;
		}

		$payload = array_merge($payload, $this->options);
		return $payload;
	}
}
