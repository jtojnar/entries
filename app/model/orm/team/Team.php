<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Team
 * @property string $name
 * @property string $genderclass
 * @property string $ageclass
 * @property int $duration
 * @property string $message
 * @property string $status {default registered} {enum self::REGISTERED self::PAID}
 * @property DateTime $timestamp {default now}
 * @property string $ip
 * @property string $password
 *
 * @property OneHasMany|Person[] $persons {1:m PersonRepository $team}
 */
class Team extends Entity {
	const REGISTERED = 'registered';
	const PAID = 'paid';
}
