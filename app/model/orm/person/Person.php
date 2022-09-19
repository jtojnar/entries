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
final class Person extends Entity {
	public const MALE = 'male';
	public const FEMALE = 'female';

	public function getJsonData(): \stdClass {
		$data = Json::decode($this->details);
		\assert($data instanceof \stdClass); // For PHPStan.

		return $data;
	}

	public function setJsonData(array|\stdClass $data): void {
		$this->details = \is_array($data) && \count($data) == 0 ? '{}' : Json::encode($data);
	}
}
