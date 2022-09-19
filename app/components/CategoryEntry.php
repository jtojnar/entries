<?php

declare(strict_types=1);

namespace App\Components;

use App\Model\CategoryData;
use Contributte\Translation\Wrappers\NotTranslate;
use Nette;

/**
 * Input field for choosing team category.
 * It can be used for either editing team category or filtering
 * teams by category.
 */
final class CategoryEntry extends Nette\Forms\Controls\SelectBox {
	/**
	 * @param string $label label of the field
	 * @param CategoryData $categories categories defined in the configuration
	 * @param bool $showAll when in filtering mode, it is useful to add “All” option as a “const true” filter
	 */
	public function __construct(string $label, CategoryData $categories, bool $showAll = false) {
		if ($categories->areNested()) {
			$items = array_map(function($group) use ($showAll) {
				$categoryArray = array_map([self::class, 'labelKey'], $group);

				if ($showAll) {
					$allKey = implode('|', array_keys($group));
					$categoryArray = [$allKey => 'messages.team.list.filter.category.all'] + $categoryArray;
				}

				return $categoryArray;
			}, $categories->getCategoryTree());
		} else {
			$items = array_map([self::class, 'labelKey'], $categories->getCategoryTree());
		}

		parent::__construct($label, $items);
	}

	private static function labelKey(array $category): NotTranslate {
		return new NotTranslate($category['label']);
	}
}
