<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Utils\Json;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Team.
 *
 * @property int $id {primary}
 * @property string $name
 * @property string $category
 * @property string $message
 * @property string $status {default registered} {enum self::REGISTERED, self::PAID}
 * @property \DateTimeImmutable $timestamp {default now}
 * @property array $jsonData {virtual}
 * @property string $details
 * @property string $ip
 * @property string $password
 * @property OneHasMany|Person[] $persons {1:m Person::$team}
 * @property OneHasMany|Invoice[] $invoices {1:m Invoice::$team}
 * @property Invoice $lastInvoice {virtual}
 */
class Team extends Entity {
	public const REGISTERED = 'registered';
	public const PAID = 'paid';

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

	public function getterLastInvoice(): Invoice {
		$invoice = $this->invoices->get()->orderBy(['timestamp' => 'DESC'])->fetch();

		if ($invoice === null) {
			throw new \Exception('Team has no invoice!');
		}

		return $invoice;
	}
}
