<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\DeepSeek;

use AIAccess;
use AIAccess\Role;


/**
 * DeepSeek implementation of a chat session state container.
 */
final class Chat extends AIAccess\Chat
{
	/** @var array<string, mixed> */
	private array $options = [];


	/**
	 * Sets options specific to this DeepSeek chat session.
	 *
	 * @param  ?int  $maxOutputTokens  Maximum tokens to generate (max_tokens).
	 * @param  ?float  $temperature  Controls randomness (0.0-2.0). Ignored by deepseek-reasoner.
	 * @param  ?float  $topP  Nucleus sampling parameter (0.0-1.0). Ignored by deepseek-reasoner.
	 * @param  ?float  $frequencyPenalty  Penalizes new tokens based on frequency (-2.0 to 2.0). Ignored by deepseek-reasoner.
	 * @param  ?float  $presencePenalty  Penalizes new tokens based on presence (-2.0 to 2.0). Ignored by deepseek-reasoner.
	 * @param  string|string[]|null  $stop  Sequences where the API will stop generating.
	 * @param  ?bool  $stream  Enable streaming response.
	 * @param  ?array  $responseFormat  Specify output format (e.g., ['type' => 'json_object']).
	 * @param  ?array  $tools  List of tools the model may call. Not supported by deepseek-reasoner.
	 * @param  string|array|null  $toolChoice  Controls which tool is called. Not supported by deepseek-reasoner.
	 */
	public function setOptions(
		?int $maxOutputTokens = null,
		?float $temperature = null,
		?float $topP = null,
		?float $frequencyPenalty = null,
		?float $presencePenalty = null,
		string|array|null $stop = null,
		?bool $stream = null,
		?array $responseFormat = null,
		?array $tools = null,
		string|array|null $toolChoice = null,
	): static
	{
		$this->options = array_merge($this->options, array_filter(
			[
				'max_tokens' => $maxOutputTokens,
				'temperature' => $temperature,
				'top_p' => $topP,
				'frequency_penalty' => $frequencyPenalty,
				'presence_penalty' => $presencePenalty,
				'stop' => $stop,
				'stream' => $stream,
				'response_format' => $responseFormat,
				'tools' => $tools,
				'tool_choice' => $toolChoice,
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
	 * Builds the payload for the DeepSeek API chat completions request.
	 */
	private function buildPayload(): array
	{
		if (!$this->messages) {
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

		// deepseek-reasoner specific parameter handling
		if ($this->model === 'deepseek-reasoner') {
			unset(
				$payload['temperature'],
				$payload['top_p'],
				$payload['frequency_penalty'],
				$payload['presence_penalty'],
				$payload['tools'],
				$payload['tool_choice'],
				// logprobs and top_logprobs would cause errors, ensure they aren't set via options
				$payload['logprobs'],
				$payload['top_logprobs'],
			);
		}

		return $payload;
	}
}
