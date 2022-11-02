<?php

declare(strict_types=1);

namespace App\Components;

use App\Model\CategoryData;
use Contributte\Translation\Wrappers\NotTranslate;

/**
 * Input field for choosing team category.
 * It can be used for either editing team category or filtering
 * teams by category.
 */
final class CategoryEntry extends ObjectSelectBox {
	/**
	 * @param string $label label of the field
	 * @param CategoryData $categories categories defined in the configuration
	 * @param bool $showAll when in filtering mode, it is useful to add “All” option as a “const true” filter
	 */
	public function __construct(string $label, CategoryData $categories, bool $showAll = false) {
		$categoryTree = $categories->getCategoryTree();
		if ($categories->areNested()) {
			$items = array_map(
				function(string $groupKey, array $group) use ($showAll): OptGroup {
					$categoryArray = array_map(self::labelKey(...), $group);

					if ($showAll) {
						$allKey = implode('|', array_keys($group));
						$categoryArray = [$allKey => 'messages.team.list.filter.category.all'] + $categoryArray;
					}

					return new OptGroup(
						label: new NotTranslate($groupKey),
						options: $categoryArray,
					);
				},
				array_keys($categoryTree),
				array_values($categoryTree)
			);
		} else {
			$items = array_map(self::labelKey(...), $categoryTree);
		}

		parent::__construct($label, $items);
	}

	private static function labelKey(array $category): NotTranslate {
		return new NotTranslate($category['label']);
	}
}
