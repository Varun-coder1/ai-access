<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess;


/**
 * Represents a batch job containing multiple chat requests.
 */
interface Batch
{
	/**
	 * Creates a new chat request to be included in the batch.
	 */
	function createChat(string $model, string $customId): Chat;

	/**
	 * Submits all added chat requests as a new batch job.
	 */
	function submit(): BatchResponse;
}
