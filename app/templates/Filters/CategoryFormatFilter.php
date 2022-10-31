<?php

declare(strict_types=1);

namespace App\Templates\Filters;

use App\Model\CategoryData;
use App\Model\Team;

final class CategoryFormatFilter {
	public function __construct(
		private readonly CategoryData $categories,
	) {
	}

	public function __invoke(Team $team): string {
		$categoryData = $this->categories->getCategoryData();

		if (isset($categoryData[$team->category])) {
			return $categoryData[$team->category]['label'];
		}

		return $team->category;
	}
}
