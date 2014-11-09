<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nette\Utils\Json;
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
 * @property array $jsonData {virtual}
 * @property string $details
 * @property string $ip
 * @property string $password
 *
 * @property OneHasMany|Person[] $persons {1:m PersonRepository $team}
 */
class Team extends Entity {
	const REGISTERED = 'registered';
	const PAID = 'paid';

	public function getJsonData() {
		return Json::decode($this->details);
	}

	public function setJsonData($data) {
		$this->details = Json::encode($data);
	}
}
