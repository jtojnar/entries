<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Fields;

use App\Locale\Translated;
use Contributte\Translation\Wrappers\NotTranslate;

abstract class Field {
	public function __construct(
		public readonly string $name,
		public readonly string|Translated|NotTranslate $label,
		public readonly bool $public,
		public readonly bool $disabled,
		public readonly string|Translated|NotTranslate|null $description,
		/** @var ?string[] */
		public readonly ?array $applicableCategories,
	) {
	}

	abstract public function getType(): string;
}
