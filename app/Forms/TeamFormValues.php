<?php

declare(strict_types=1);

namespace App\Forms;

final class TeamFormValues {
	/** @var array<array-key, mixed> */
	public readonly array $extraFields;

	public function __construct(
		public readonly string $name,
		public readonly string $category,
		mixed ...$extraFields,
	) {
		$this->extraFields = $extraFields;
	}
}
