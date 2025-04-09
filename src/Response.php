<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess;


/**
 * Represents the response received from the AI model.
 */
interface Response
{
	/**
	 * Gets the main textual content of the AI's response.
	 */
	function getText(): string;

	/**
	 * Gets the reason the model stopped generating output (provider-specific).
	 */
	function getFinishReason(): ?string;

	/**
	 * Gets provider-specific token usage information, if available.
	 * Keys: 'inputTokens', 'outputTokens', 'reasoningTokens' and other provider specific
	 */
	function getUsage(): ?array;

	/**
	 * Gets the raw, unprocessed response data from the API provider.
	 */
	function getRawResponse(): mixed;
}
