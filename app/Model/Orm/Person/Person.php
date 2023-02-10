<?php

declare(strict_types=1);

namespace App\Model\Orm\Person;

use App\Model\Orm\ItemReservation\ItemReservation;
use App\Model\Orm\Team\Team;
use Nette\Utils\Json;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Person.
 *
 * @property int $id {primary}
 * @property Team|null $team {m:1 Team::$persons}
 * @property string $firstname
 * @property string $lastname
 * @property string $gender {enum self::GENDER_*}
 * @property \DateTimeImmutable $birth
 * @property OneHasMany<ItemReservation> $itemReservations {1:m ItemReservation::$person}
 * @property array $jsonData {virtual}
 * @property string $details
 * @property string $email
 * @property bool $contact {default false}
 *
 * @property-phpstan self::GENDER_* $gender
 */
final class Person extends Entity {
	public const GENDER_MALE = 'male';
	public const GENDER_FEMALE = 'female';

	public function getJsonData(): \stdClass {
		$data = Json::decode($this->details);
		\assert($data instanceof \stdClass); // For PHPStan.

		return $data;
	}

	public function setJsonData(array|\stdClass $data): void {
		$this->details = \is_array($data) && \count($data) == 0 ? '{}' : Json::encode($data);
	}
}
