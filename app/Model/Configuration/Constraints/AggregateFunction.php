<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

use App\Helpers\Iter;

enum AggregateFunction: string {
	case Sum = 'sum';
	case Min = 'min';
	case Max = 'max';

	/**
	 * @param iterable<int> $values
	 */
	public function __invoke(iterable $values): int {
		return match ($this) {
			self::Sum => Iter::reduce(
				$values,
				static fn(int $carry, int $value): int => $value + $carry,
				0,
			),
			self::Min => Iter::reduce(
				$values,
				static fn(int $carry, int $value): int => $value < $carry ? $value : $carry,
				\PHP_INT_MAX,
			),
			self::Max => Iter::reduce(
				$values,
				static fn(int $carry, int $value): int => $value > $carry ? $value : $carry,
				\PHP_INT_MIN,
			),
		};
	}
}
