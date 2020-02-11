<?php

declare(strict_types=1);

namespace App\Templates\Filters;

use App\Model\CategoryData;
use App\Model\Team;

class CategoryFormatFilter {
	/** @var CategoryData */
	private $categories;

	public function __construct(CategoryData $categories) {
		$this->categories = $categories;
	}

	public function __invoke(Team $team): string {
		$categoryData = $this->categories->getCategoryData();

		if (isset($categoryData[$team->category])) {
			return $categoryData[$team->category]['label'];
		}

		return $team->category;
	}
}
