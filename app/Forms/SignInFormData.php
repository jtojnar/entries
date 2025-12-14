<?php

declare(strict_types=1);

namespace App\Forms;

final readonly class SignInFormData {
	public function __construct(
		public bool $remember,
		public string $teamid,
		public string $password,
	) {
	}
}
