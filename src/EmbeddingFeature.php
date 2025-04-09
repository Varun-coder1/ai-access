<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess;


/**
 * Provides access points to embedding calculation.
 */
interface EmbeddingFeature
{
	/**
	 * Calculates embeddings for the given input text(s) using a specified model.
	 * @param  string[]  $input
	 * @return Embedding[]
	 */
	function calculateEmbeddings(string $model, array $input): array;
}
