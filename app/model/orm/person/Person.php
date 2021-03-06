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

	/**
	 * @return \stdClass
	 */
	public function getJsonData() {
		return Json::decode($this->details);
	}

	/**
	 * @param array|\stdClass $data
	 */
	public function setJsonData($data): void {
		$this->details = \is_array($data) && \count($data) == 0 ? '{}' : Json::encode($data);
	}
}
