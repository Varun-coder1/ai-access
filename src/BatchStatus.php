<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess;


/**
 * Enum representing the status of a batch job.
 */
enum BatchStatus
{
	case InProgress;
	case Completed;
	case Failed;
	case Other;
}
