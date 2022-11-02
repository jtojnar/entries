<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

enum EqualityOperator: string {
	case Equal = '=';

	public function __invoke(mixed $a, mixed $b): bool {
		return match ($this) {
			self::Equal => $a === $b,
		};
	}
}
