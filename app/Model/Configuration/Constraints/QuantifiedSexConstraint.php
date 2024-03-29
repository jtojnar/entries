<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Constraints;

use App\Helpers\Iter;
use ArrayAccess;

class QuantifiedSexConstraint implements Constraint {
	public function __construct(
		public readonly Quantifier $quantifier,
		public readonly EqualityOperator $operator,
		public readonly Sex $targetSex,
	) {
	}

	public function admits(iterable $members): bool {
		$sexes = Iter::map(
			static fn(ArrayAccess $member): ?Sex => \is_string($member['gender']) ? Sex::from($member['gender']) : null,
			$members,
		);
		$sexes = Iter::filterNull($sexes);

		return ($this->quantifier)(fn(Sex $sex): bool => ($this->operator)($sex, $this->targetSex), $sexes);
	}

	public function getErrorMessage(): string {
		return 'messages.team.error.gender_mismatch';
	}
}
