<?php

declare(strict_types=1);

namespace App\Model\Orm\Person;

use Nextras\Orm\Repository\Repository;

final class PersonRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Person::class];
	}
}
