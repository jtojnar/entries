<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Team
 *
 * @property int $id {primary}
 * @property string $name
 * @property string $genderclass
 * @property string $ageclass
 * @property int $duration
 * @property string $message
 * @property string $status {default registered} {enum self::REGISTERED, self::PAID}
 * @property DateTime $timestamp {default now}
 * @property array $jsonData {virtual}
 * @property string $details
 * @property string $ip
 * @property string $password
 * @property OneHasMany|Person[] $persons {1:m Person::$team}
 * @property OneHasMany|Invoice[] $invoices {1:m Invoice::$team}
 * @property-read Invoice $lastInvoice {virtual}
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

	public function getterLastInvoice() {
		return $this->invoices->get()->orderBy(['timestamp' => 'DESC'])->fetch();
	}
}
