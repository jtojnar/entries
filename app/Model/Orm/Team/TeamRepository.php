<?php

declare(strict_types=1);

namespace App\Model\Orm\Team;

use Nextras\Orm\Repository\Repository;

final class TeamRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Team::class];
	}
}
