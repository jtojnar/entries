<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

enum ComparisonOperator: string {
	case LessThan = '<';
	case LessThanOrEqual = '<=';
	case MoreThan = '>';
	case MoreThanOrEqual = '>=';

	public function __invoke(mixed $a, mixed $b): bool {
		return match ($this) {
			self::LessThan => $a < $b,
			self::LessThanOrEqual => $a <= $b,
			self::MoreThan => $a > $b,
			self::MoreThanOrEqual => $a >= $b,
		};
	}
}
