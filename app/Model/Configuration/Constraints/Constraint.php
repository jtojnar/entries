<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

interface Constraint {
	/**
	 * @param iterable<iterable<string, mixed>> $members
	 */
	public function admits(iterable $members): bool;

	public function getErrorMessage(): string;
}
