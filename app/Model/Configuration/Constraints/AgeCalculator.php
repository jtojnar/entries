<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

use App\Forms\TeamFormPersonValues;

trait AgeCalculator {
	private function getAgeFromPerson(TeamFormPersonValues $person): ?int {
		$age = $person->birth?->diff($this->eventDate, true)->y;

		return $age;
	}
}
