<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration;

use DateTimeInterface;

/**
 * Check whether categories in config.neon are nested.
 *
 * @param non-empty-array $categories
 */
function are_categories_nested(array $categories): bool {
	foreach ($categories as $category) {
		return isset($category['categories']);
	}
}

final class CategoryData {
	/** @var non-empty-array<string, Category> List of categories across all groups */
	public readonly array $allCategories;

	private function __construct(
		/** @var non-empty-array<Category>|non-empty-array<CategoryGroup> */
		public readonly array $categoriesOrGroups,
		/** @var bool Whether categories are nested */
		public readonly bool $nested,
	) {
		$allCategories = [];
		if ($nested) {
			/** @var non-empty-array<CategoryGroup> */ // For PHPStan
			$groups = $categoriesOrGroups;
			$categoryKeys = [];

			foreach ($groups as $group) {
				foreach ($group->categories as $category) {
					if (isset($categoryKeys[$category->name])) {
						throw new InvalidConfigurationException("Category “{$category->name}” is defined in both “{$group->key}” and “{$categoryKeys[$category->name]}”.");
					}

					$allCategories[$category->name] = $category;
					$categoryKeys[$category->name] = $group->key;
				}
			}
			$this->allCategories = $allCategories;
		} else {
			/** @var non-empty-array<Category> */ // For PHPStan
			$categories = $categoriesOrGroups;
			foreach ($categories as $category) {
				$allCategories[$category->name] = $category;
			}
			$this->allCategories = $allCategories;
		}
	}

	public static function from(
		array $categories,
		Fees $fees,
		DateTimeInterface $eventDate,
		array $allLocales,
	): self {
		if (\count($categories) === 0) {
			throw new InvalidConfigurationException('No categories defined.');
		}

		if (are_categories_nested($categories)) {
			$groups = $categories;

			$categoryGroups = array_map(
				fn(string $groupKey, array $group) => CategoryGroup::from(
					$groupKey,
					Helpers::parseLabel("category group #{$groupKey}", $group, $allLocales),
					$group,
					$fees,
					$eventDate,
				),
				array_keys($groups),
				$groups,
			);

			return new self(
				$categoryGroups,
				nested: true,
			);
		} else {
			$categoriesData = array_map(
				fn(string $categoryKey, array $category) => Category::from(
					$categoryKey,
					$category,
					$fees,
					$eventDate,
				),
				array_keys($categories),
				$categories,
			);

			return new self(
				$categoriesData,
				nested: false,
			);
		}
	}
}
