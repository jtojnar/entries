<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class TeamRepository extends Repository {
	public static function getEntityClassNames() {
		return [Team::class];
	}
}
