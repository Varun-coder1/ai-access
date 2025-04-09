<?php

/**
 * This file is part of the AI Access library.
 * Copyright (c) 2024 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace AIAccess;


/**
 * Represents a chat message.
 */
class Message
{
	public function __construct(
		private string $text,
		private Role $role,
	) {
	}


	public function getRole(): Role
	{
		return $this->role;
	}


	public function getText(): string
	{
		return $this->text;
	}
}
