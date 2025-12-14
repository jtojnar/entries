<?php

declare(strict_types=1);

namespace App\Model\Orm\Team;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Team>
 */
final class TeamRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Team::class];
	}
}
