<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess\Claude;

use AIAccess;

/**
 * Service responsible for creating and managing Claude Batch API jobs.
 */
final class Batch implements AIAccess\Batch
{
	/** @var Chat[] */
	private array $chats = [];


	public function __construct(
		private Client $client,
	) {
	}


	/**
	 * Creates a new chat request to be included in the batch.
	 */
	public function createChat(string $model, string $customId): Chat
	{
		if (isset($this->chats[$customId])) {
			throw new AIAccess\LogicException("Chat with custom ID '{$customId}' already exists in this batch.");
		}
		return $this->chats[$customId] = new Chat($this->client, $model);
	}


	/**
	 * Submits all added chat requests as a new batch job.
	 * @throws AIAccess\ApiException On API errors during job submission.
	 * @throws AIAccess\NetworkException On connection problems.
	 */
	public function submit(): BatchResponse
	{
		if (!$this->chats) {
			throw new AIAccess\LogicException('Cannot submit batch job: No chat requests added.');
		}

		$requests = [];
		foreach ($this->chats as $customId => $chat) {
			$requests[] = [
				'custom_id' => $customId,
				'params' => $chat->buildPayload(),
			];
		}

		$payload = [
			'requests' => $requests,
		];
		$rawResponse = $this->client->sendRequest('v1/messages/batches', $payload);
		return new BatchResponse($this->client, $rawResponse);
	}
}
