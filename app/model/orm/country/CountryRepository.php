<?php

declare(strict_types=1);

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class CountryRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Country::class];
	}

	public function fetchIdNamePairs() {
		return $this->findAll()->orderBy('name')->fetchPairs('id', 'name');
	}
}
