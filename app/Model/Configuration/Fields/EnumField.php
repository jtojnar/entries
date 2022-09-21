<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Fields;

use App\Locale\Translated;
use Contributte\Translation\Wrappers\NotTranslate;
use Money\Money;

class EnumField extends Field implements LimitableField {
	/**
	 * @param ?string[] $applicableCategories
	 */
	public function __construct(
		string $name,
		string|Translated|NotTranslate $label,
		bool $public,
		bool $disabled,
		/** @var array<string, Item> */
		public readonly array $options,
		string|Translated|NotTranslate|null $description,
		?array $applicableCategories,
		public readonly ?string $limitName,
		public readonly ?Money $fee,
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

	public function getLimitName(): ?string {
		return $this->limitName;
	}

	public function getType(): string {
		return 'enum';
	}
}
