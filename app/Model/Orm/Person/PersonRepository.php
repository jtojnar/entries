<?php

declare(strict_types=1);

namespace App\Model\Orm\Person;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Person>
 */
final class PersonRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Person::class];
	}
}
