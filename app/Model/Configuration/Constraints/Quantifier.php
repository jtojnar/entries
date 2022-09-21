<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

use App\Helpers\Iter;

enum Quantifier: string {
	case All = 'all';
	case Some = 'some';

	/**
	 * @template T
	 *
	 * @param callable(T): bool $predicate
	 * @param iterable<T> $values
	 */
	public function __invoke(callable $predicate, iterable $values): bool {
		return match ($this) {
			self::All => Iter::all($values, $predicate),
			self::Some => Iter::any($values, $predicate),
		};
	}
}
