<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2026 Jan Tojnar

declare(strict_types=1);

namespace App\Forms;

final readonly class ComposeFormValues {
	/** @var list<int> */
	public array $recipients;

	public function __construct(
		public string $subject,
		string $recipients,
		public string $sender,
		public string $body,
	) {
		$teamsIds = explode(',', $recipients);

		$teamsIds = array_map(
			trim(...),
			$teamsIds,
		);

		$teamsIds = array_filter(
			$teamsIds,
			static fn(string $id): bool => $id !== '',
		);

		$teamsIds = array_map(
			static fn(string $id): int => (int) $id,
			$teamsIds,
		);

		$this->recipients = $teamsIds;
	}
}
