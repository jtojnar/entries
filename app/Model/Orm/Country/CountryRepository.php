<?php

declare(strict_types=1);

namespace App\Model\Orm\Country;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Country>
 */
final class CountryRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Country::class];
	}

	/**
	 * @return array<int, string>
	 */
	public function fetchIdNamePairs() {
		/** @var array<int, string> */
		$countries = $this->findAll()->orderBy('name')->fetchPairs('id', 'name');

		return $countries;
	}
}
