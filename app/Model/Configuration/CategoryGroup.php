<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration;

use App\Locale\Translated;
use Contributte\Translation\Wrappers\NotTranslate;
use DateTimeInterface;

final readonly class CategoryGroup {
	public function __construct(
		public string $key,
		public string|Translated|NotTranslate $label,
		/** @var non-empty-array<Category> */
		public array $categories,
	) {
	}

	public static function from(
		string $key,
		string|Translated|NotTranslate $label,
		array $group,
		Fees $parentFees,
		DateTimeInterface $eventDate,
	): self {
		$fees = Fees::from("categories.$key", $group['fees'] ?? [], $parentFees);

		if (!isset($group['categories']) || !\is_array($group['categories']) || \count($group['categories']) === 0) {
			throw new InvalidConfigurationException("Category group #{$key} lacks categories");
		}

		$categoriesRaw = $group['categories'];

		$categories = array_map(
			fn(string $categoryKey, array $category): Category => Category::from(
				$categoryKey,
				$category,
				$fees,
				$eventDate,
			),
			array_keys($categoriesRaw),
			array_values($categoriesRaw),
		);

		return new self(
			key: $key,
			label: $label,
			categories: $categories,
		);
	}
}
