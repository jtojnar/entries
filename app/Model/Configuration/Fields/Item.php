<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Fields;

use App\Locale\Translated;
use Contributte\Translation\Wrappers\NotTranslate;
use Money\Money;
use Override;

final readonly class Item implements LimitableField {
	public function __construct(
		public string $name,
		public string|Translated|NotTranslate $label,
		public bool $disabled,
		public ?bool $default,
		public ?string $limitName,
		public ?Money $fee,
	) {
	}

	#[Override]
	public function getLimitName(): ?string {
		return $this->limitName;
	}
}
