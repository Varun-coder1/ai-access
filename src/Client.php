<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess;


/**
 * Core interface for AI service clients.
 * Provides access points to different AI functionalities.
 */
interface Client
{
	/**
	 * Creates a new chat session for the specified AI model.
	 */
	function createChat(string $model): Chat;

	/**
	 * Sets or updates client-wide options.
	 */
	function setOptions(/* Implementation defines named arguments */): static;
}
