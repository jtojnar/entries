<?php

declare(strict_types=1);

namespace App\Forms;

final readonly class TeamFormValues {
	/** @var array<array-key, mixed> */
	public array $extraFields;

	public function __construct(
		public string $name,
		public string $category,
		mixed ...$extraFields,
	) {
		$this->extraFields = $extraFields;
	}
}
