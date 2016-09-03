<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class PersonRepository extends Repository {
	public static function getEntityClassNames() {
		return [Person::class];
	}
}
