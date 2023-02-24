<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

use ArrayAccess;

trait AgeCalculator {
	private function getAgeFromPerson(ArrayAccess $person): ?int {
		if (!$person['birth'] instanceof \DateTimeInterface) {
			return null;
		}

		$age = $person['birth']->diff($this->eventDate, true)->y;

		return $age;
	}
}
