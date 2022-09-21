<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Fields;

use App\Locale\Translated;
use Contributte\Translation\Wrappers\NotTranslate;
use Money\Money;

final class Item implements LimitableField {
	public function __construct(
		public readonly string $name,
		public readonly string|Translated|NotTranslate $label,
		public readonly bool $disabled,
		public readonly ?bool $default,
		public readonly ?string $limitName,
		public readonly ?Money $fee,
	) {
	}

	public function getLimitName(): ?string {
		return $this->limitName;
	}
}
