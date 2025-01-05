<?php

declare(strict_types=1);

namespace App\Components;

use App\Model\Configuration\Category;
use App\Model\Configuration\CategoryGroup;
use App\Model\Configuration\Entries;
use Contributte\Translation\Wrappers\NotTranslate;

/**
 * Input field for choosing team category.
 * It can be used for either editing team category or filtering
 * teams by category.
 */
final class CategoryEntry extends ObjectSelectBox {
	/**
	 * @param string $label label of the field
	 * @param bool $showAll when in filtering mode, it is useful to add “All” option as a “const true” filter
	 */
	public function __construct(string $label, Entries $entries, bool $showAll = false) {
		$categoryTree = $entries->categories->categoriesOrGroups;
		if ($entries->categories->nested) {
			/** @var non-empty-array<CategoryGroup> */
			$categoryGroups = $categoryTree;
			$items = array_combine(
				array_map(
					static fn(CategoryGroup $group): string => $group->key,
					$categoryGroups,
				),
				array_map(
					function(CategoryGroup $group) use ($showAll): OptGroup {
						$categoryArray = array_combine(
							array_map(
								static fn(Category $category): string => $category->name,
								$group->categories,
							),
							array_map(
								self::labelKey(...),
								$group->categories,
							),
						);

						if ($showAll) {
							$allKey = implode('|', array_keys($group->categories));
							$categoryArray = [$allKey => 'messages.team.list.filter.category.all'] + $categoryArray;
						}

						return new OptGroup(
							label: new NotTranslate($group->key),
							options: $categoryArray,
						);
					},
					$categoryGroups,
				),
			);
		} else {
			/** @var non-empty-array<Category> */
			$categories = $categoryTree;
			$items = array_combine(
				array_map(
					static fn(Category $category): string => $category->name,
					$categories,
				),
				array_map(
					self::labelKey(...),
					$categories,
				),
			);
		}

		parent::__construct($label, $items);
	}

	private static function labelKey(Category $category): NotTranslate {
		return new NotTranslate($category->name);
	}
}
