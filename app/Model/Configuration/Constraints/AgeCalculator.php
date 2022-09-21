<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

use ArrayAccess;

trait AgeCalculator {
	private function getAgeFromPerson(ArrayAccess $person): int {
		\assert($person['birth'] instanceof \DateTimeInterface); // For PHPStan.

		$age = $diff = $person['birth']->diff($this->eventDate, true)->y;

		return $age;
	}
}
