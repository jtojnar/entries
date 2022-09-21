<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Fields;

use App\Locale\Translated;
use Contributte\Translation\Wrappers\NotTranslate;

class CountryField extends Field {
	/**
	 * @param ?string[] $applicableCategories
	 */
	public function __construct(
		string $name,
		string|Translated|NotTranslate $label,
		bool $public,
		bool $disabled,
		public readonly ?int $default,
		string|Translated|NotTranslate|null $description,
		?array $applicableCategories,
	) {
		parent::__construct(
			$name,
			$label,
			$public,
			$disabled,
			$description,
			$applicableCategories,
		);
	}

	public function getType(): string {
		return 'country';
	}
}
