<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Helpers;

final class Op {
	/**
	 * @template T
	 *
	 * @param T $value
	 *
	 * @return T
	 */
	public static function id(mixed $value): mixed {
		return $value;
	}

	public static function int(string $value): int {
		return (int) $value;
	}

	public static function lt(mixed $a, mixed $b): bool {
		return $a < $b;
	}

	public static function le(mixed $a, mixed $b): bool {
		return $a <= $b;
	}

	public static function idnt(mixed $a, mixed $b): bool {
		return $a === $b;
	}

	public static function ge(mixed $a, mixed $b): bool {
		return $a >= $b;
	}

	public static function gt(mixed $a, mixed $b): bool {
		return $a > $b;
	}
}
