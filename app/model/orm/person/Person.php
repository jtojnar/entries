<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Utils\Json;
use Nextras\Orm\Entity\Entity;

/**
 * Person.
 *
 * @property int $id {primary}
 * @property Team|null $team {m:1 Team::$persons}
 * @property string $firstname
 * @property string $lastname
 * @property string $gender {enum self::MALE, self::FEMALE}
 * @property \DateTimeImmutable $birth
 * @property array $jsonData {virtual}
 * @property string $details
 * @property string $email
 * @property bool $contact {default false}
 */
class Person extends Entity {
	public const MALE = 'male';
	public const FEMALE = 'female';

	public function getJsonData() {
		return Json::decode($this->details);
	}

	public function setJsonData($data): void {
		$this->details = Json::encode($data);
	}
}
