<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

use App\Helpers\Iter;

class AggregateAgeConstraint implements Constraint {
	use AgeCalculator;

	public function __construct(
		public readonly AggregateFunction $function,
		public readonly EqualityOperator|ComparisonOperator $operator,
		public readonly int $targetAge,
		private readonly \DateTimeInterface $eventDate,
	) {
	}

	public function admits(iterable $members): bool {
		$ages = Iter::filterNull(Iter::map($this->getAgeFromPerson(...), $members));

		return ($this->operator)(($this->function)($ages), $this->targetAge);
	}

	public function getErrorMessage(): string {
		return 'messages.team.error.age_mismatch';
	}
}
