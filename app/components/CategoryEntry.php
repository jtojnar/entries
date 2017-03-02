<?php

namespace App\Components;

use App\Model\CategoryData;
use Nette;

class CategoryEntry extends Nette\Forms\Controls\SelectBox {
	public function __construct($label, CategoryData $categories, $showAll = false) {
		if ($categories->areNested()) {
			$items = array_map(function($group) use ($showAll) {
				$categoryArray = array_map(['self', 'labelKey'], $group);

				if ($showAll) {
					$allKey = implode('|', array_keys($group));
					$categoryArray = [$allKey => 'messages.team.list.filter.category.all'] + $categoryArray;
				}

				return $categoryArray;
			}, $categories->getCategoryTree());
		} else {
			$items = array_map(['self', 'labelKey'], $categories->getCategoryTree());
		}

		parent::__construct($label, $items);
	}

	private static function labelKey($category) {
		return $category['label'];
	}
}
