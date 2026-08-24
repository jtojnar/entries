<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2026 Jan Tojnar

declare(strict_types=1);

namespace App\Forms;

final readonly class TeamListActionFormValues {
	/** @var list<string> */
	public array $selectedTeamIds;

	// @phpstan-ignore constructor.unusedParameter (Without any required arguments, `Container::getUntrustedValues()` will not use the constructor.)
	public function __construct(
		string $_hack,
		mixed ...$values,
	) {
		$selectedTeamIds = array_map(
			static fn($name): string => substr((string) $name, \strlen('team_')),
			array_keys(
				array_filter(
					$values,
					static fn($value, $name): bool => str_starts_with((string) $name, 'team_') && \is_bool($value) && $value,
					\ARRAY_FILTER_USE_BOTH
				)
			)
		);

		$this->selectedTeamIds = $selectedTeamIds;
	}
}
