<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2026 Jan Tojnar

declare(strict_types=1);

namespace App\Forms;

use DateTimeImmutable;

final readonly class TeamFormPersonValues {
	/** @var array<array-key, mixed> */
	public array $extraFields;

	public function __construct(
		public string $firstname,
		public string $lastname,
		public string $gender,
		public ?DateTimeImmutable $birth,
		public string $email,
		mixed ...$extraFields,
	) {
		$this->extraFields = $extraFields;
	}
}
