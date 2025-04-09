<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess;


/**
 * Provides access to batch processing functionality.
 */
interface BatchFeature
{
	/**
	 * Creates a new batch job container.
	 */
	function createBatch(): Batch;

	/**
	 * Lists existing batch jobs.
	 * @return BatchResponse[]
	 */
	function listBatches(/* Implementation defines named arguments */): array;

	/**
	 * Retrieves the current status and details of a specific batch job by its ID.
	 */
	function retrieveBatch(string $id): BatchResponse;

	/**
	 * Attempts to cancel a batch job that is currently in progress.
	 * @return bool True if cancellation was initiated successfully, false otherwise
	 */
	function cancelBatch(string $id): bool;
}
