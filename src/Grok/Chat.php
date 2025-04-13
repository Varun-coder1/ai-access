<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\Grok;

use AIAccess;
use AIAccess\Role;


/**
 * Grok (xAI) implementation of a chat session state container.
 */
final class Chat extends AIAccess\Chat
{
	/** @var array<string, mixed> */
	private array $options = [];


	/**
	 * Sets options specific to this Grok chat session.
	 *
	 * @param  ?int  $maxOutputTokens  Maximum completion tokens (max_completion_tokens).
	 * @param  ?float  $temperature  Sampling temperature (0.0-2.0).
	 * @param  ?float  $topP  Nucleus sampling parameter (0.0-1.0).
	 * @param  ?float  $frequencyPenalty  Penalizes new tokens based on frequency (-2.0 to 2.0).
	 * @param  ?float  $presencePenalty  Penalizes new tokens based on presence (-2.0 to 2.0).
	 * @param  string|string[]|null  $stop  Sequences where the API will stop generating.
	 * @param  ?bool  $stream  Enable streaming response.
	 * @param  ?int  $seed  Seed for deterministic sampling (best effort).
	 * @param  ?array  $responseFormat  Specify output format (e.g., ['type' => 'json_object']).
	 * @param  ?string $reasoningEffort Control thinking effort for reasoning models ('low', 'high').
	 * @param  ?array  $tools  List of tools the model may call.
	 * @param  string|array|null  $toolChoice  Controls which tool is called.
	 */
	public function setOptions(
		?int $maxOutputTokens = null,
		?float $temperature = null,
		?float $topP = null,
		?float $frequencyPenalty = null,
		?float $presencePenalty = null,
		string|array|null $stop = null,
		?bool $stream = null,
		?int $seed = null,
		?array $responseFormat = null,
		?string $reasoningEffort = null,
		?array $tools = null,
		string|array|null $toolChoice = null,
	): static
	{
		$this->options = array_merge($this->options, array_filter(
			[
				'max_completion_tokens' => $maxOutputTokens,
				'temperature' => $temperature,
				'top_p' => $topP,
				'frequency_penalty' => $frequencyPenalty,
				'presence_penalty' => $presencePenalty,
				'stop' => $stop,
				'stream' => $stream,
				'seed' => $seed,
				'response_format' => $responseFormat,
				'reasoning_effort' => $reasoningEffort,
				'tools' => $tools,
				'tool_choice' => $toolChoice,
				// Note: logprobs, top_logprobs could be added if needed
			],
			fn($value) => $value !== null,
		));
		return $this;
	}


	/**
	 * Generates the next response based on the current chat history and settings.
	 */
	protected function generateResponse(): Response
	{
		$rawResponse = $this->client->sendRequest('chat/completions', $this->buildPayload());
		$response = new Response($rawResponse);
		return $response;
	}


	/**
	 * Builds the payload for the Grok API chat completions request.
	 */
	private function buildPayload(): array
	{
		if (empty($this->messages)) {
			throw new AIAccess\LogicException('Cannot send request with empty message history.');
		}

		$messages = [];
		if ($this->systemInstruction !== null) {
			$messages[] = [
				'role' => 'system',
				'content' => $this->systemInstruction,
			];
		}

		foreach ($this->messages as $message) {
			$messages[] = [
				'role' => match ($message->getRole()) {
					Role::User => 'user',
					Role::Model => 'assistant',
				},
				'content' => $message->getText(),
			];
		}

		$payload = [
			'model' => $this->model,
			'messages' => $messages,
		] + $this->options;

		return $payload;
	}
}
