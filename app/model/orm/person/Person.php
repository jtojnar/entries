<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Person
 * @property Team|null $team {m:1 TeamRepository $persons}
 * @property string $firstname
 * @property Country $country {m:1 CountryRepository $persons}
 * @property string $lastname
 * @property string $gender {enum self::MALE self::FEMALE}
 * @property DateTime $birth
 * @property int|null $sportident
 * @property string $email
 * @property bool $contact {default false}
 */
class Person extends Entity {
	const MALE = 'male';
	const FEMALE = 'female';
}
