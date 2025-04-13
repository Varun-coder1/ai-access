<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\Gemini;

use AIAccess;
use AIAccess\Role;


/**
 * Gemini implementation of a chat session state container.
 */
final class Chat extends AIAccess\Chat
{
	/** @var array<string, mixed> */
	private array $options = [];


	/**
	 * Sets options specific to this Gemini chat session.
	 *
	 * @param  ?int  $maxOutputTokens  Maximum tokens to generate.
	 * @param  ?array  $safetySettings  Safety filter settings.
	 * @param  ?array  $stopSequences  Sequences where the API will stop generating.
	 * @param  ?float  $temperature  Controls randomness (0.0-1.0).
	 * @param  ?float  $topK  Top-k sampling parameter.
	 * @param  ?float  $topP  Nucleus sampling parameter.
	 */
	public function setOptions(
		?int $maxOutputTokens = null,
		?array $safetySettings = null,
		?array $stopSequences = null,
		?float $temperature = null,
		?float $topK = null,
		?float $topP = null,
	): static
	{
		$this->options = array_merge($this->options, array_filter(
			compact('maxOutputTokens', 'safetySettings', 'stopSequences', 'temperature', 'topK', 'topP'),
			fn($value) => $value !== null,
		));
		return $this;
	}


	protected function generateResponse(): Response
	{
		$rawResponse = $this->client->sendRequest('models/' . $this->model . ':generateContent', $this->buildPayload());
		$response = new Response($rawResponse);
		return $response;
	}


	/**
	 * Builds the payload for the Gemini API generateContent request.
	 */
	private function buildPayload(): array
	{
		if (!$this->messages) {
			throw new AIAccess\LogicException('Cannot send request with empty message history.');
		} elseif ($this->messages[0]->getRole() !== Role::User) {
			throw new AIAccess\LogicException('The first message must be from the user role.');
		}

		$payload = [];
		$lastRole = null;
		foreach ($this->messages as $message) {
			$role = match ($message->getRole()) {
				Role::User => 'user',
				Role::Model => 'model',
			};
			if ($lastRole === $role) {
				// For now, let the API potentially handle it.
				trigger_error("Consecutive messages with the same role ('$role') detected. Gemini requires alternating roles.", E_USER_WARNING);
			}
			$payload['contents'][] = [
				'role' => $role,
				'parts' => [['text' => $message->getText()]],
			];
			$lastRole = $role;
		}

		if ($this->systemInstruction !== null) {
			$payload['systemInstruction'] = [
				'parts' => [['text' => $this->systemInstruction]],
			];
		}

		$generationConfig = array_intersect_key(
			$this->options,
			array_flip(['temperature', 'maxOutputTokens', 'topP', 'topK', 'stopSequences']),
		);
		if ($generationConfig) {
			$payload['generationConfig'] = $generationConfig;
		}

		if (isset($this->options['safetySettings'])) {
			$payload['safetySettings'] = $this->options['safetySettings'];
		}

		return $payload;
	}
}
