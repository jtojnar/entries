<?php

declare(strict_types=1);

namespace App\Presenters\Accessory\Filters;

use App\Model\Configuration\Entries;
use App\Model\Orm\Team\Team;

final readonly class CategoryFormatFilter {
	public function __construct(
		private Entries $entries,
	) {
	}

	public function __invoke(Team $team): string {
		$categoryData = $this->entries->categories->allCategories;

		if (isset($categoryData[$team->category])) {
			return $categoryData[$team->category]->name;
		}

		return $team->category;
	}
}
