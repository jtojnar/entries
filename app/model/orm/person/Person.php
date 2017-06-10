<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nextras\Orm\Entity\Entity;

/**
 * Person
 *
 * @property int $id {primary}
 * @property Team|null $team {m:1 Team::$persons}
 * @property string $firstname
 * @property string $lastname
 * @property string $gender {enum self::MALE, self::FEMALE}
 * @property DateTime $birth
 * @property array $jsonData {virtual}
 * @property string $details
 * @property string $email
 * @property bool $contact {default false}
 */
class Person extends Entity {
	const MALE = 'male';
	const FEMALE = 'female';

	public function getJsonData() {
		return Json::decode($this->details);
	}

	public function setJsonData($data) {
		$this->details = Json::encode($data);
	}
}
