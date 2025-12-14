<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2026 Jan Tojnar

declare(strict_types=1);

namespace App\Forms;

final readonly class SignInFormValues {
	public function __construct(
		public bool $remember,
		public string $teamid,
		public string $password,
	) {
	}
}
