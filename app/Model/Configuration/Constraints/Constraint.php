<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

use App\Forms\TeamFormPersonValues;

interface Constraint {
	/**
	 * @param iterable<TeamFormPersonValues> $members
	 */
	public function admits(iterable $members): bool;

	public function getErrorMessage(): string;
}
