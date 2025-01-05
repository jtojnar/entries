<?php

declare(strict_types=1);

namespace App\Model\Orm\Country;

use Nextras\Orm\Repository\Repository;

/**
 * @extends Repository<Country>
 */
final class CountryRepository extends Repository {
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
