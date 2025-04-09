<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess;


/**
 * Chat session.
 */
abstract class Chat
{
	/** @var Message[] */
	protected array $messages = [];
	protected ?string $systemInstruction = null;


	public function __construct(
		protected readonly Client $client,
		protected string $model,
	) {
	}


	/**
	 * Sends the next message to the model or continues generation based on history.
	 * Updates internal message history with user input and model response.
	 */
	public function sendMessage(?string $message = null): Response
	{
		$save = $this->messages;
		if ($message !== null) {
			$this->addMessage($message, Role::User);
		}

		try {
			$response = $this->generateResponse();
		} catch (\Throwable $e) {
			$this->messages = $save;
			throw $e;
		}

		$responseText = $response->getText();
		if ($responseText !== '') {
			$this->addMessage($responseText, Role::Model);
		}

		return $response;
	}


	/**
	 * Adds a message to the chat history without sending it to the API.
	 */
	public function addMessage(string $message, Role $role): Message
	{
		return $this->messages[] = new Message($message, $role);
	}


	/**
	 * Sets a system-level instruction for the model.
	 */
	public function setSystemInstruction(string $instruction): static
	{
		$this->systemInstruction = $instruction;
		return $this;
	}


	/**
	 * Retrieves the current message history (user and model messages).
	 * @return Message[]
	 */
	public function getMessages(): array
	{
		return $this->messages;
	}


	abstract protected function generateResponse(): Response;
}
