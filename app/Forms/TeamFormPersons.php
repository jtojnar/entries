<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2026 Jan Tojnar

declare(strict_types=1);

namespace App\Forms;

final readonly class TeamFormPersons {
	/** @var array<array-key, TeamFormPersonValues> */
	public array $values;

	public function __construct(
		// Hack: Replicator uses numeric component names but `Container::getUntrustedValues()` will not use the constructor without any required arguments.
		TeamFormPersonValues $first,
		TeamFormPersonValues ...$persons,
	) {
		array_unshift($persons, $first);
		$this->values = $persons;
	}
}
