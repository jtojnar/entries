<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Utils\Json;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Team.
 *
 * @property int $id {primary}
 * @property string $name
 * @property string $category
 * @property string $message
 * @property string $status {default registered} {enum self::REGISTERED, self::PAID, self::WITHDRAWN}
 * @property \DateTimeImmutable $timestamp {default now}
 * @property array $jsonData {virtual}
 * @property string $details
 * @property string $ip
 * @property string $password
 * @property OneHasMany|Person[] $persons {1:m Person::$team}
 * @property OneHasMany|Invoice[] $invoices {1:m Invoice::$team}
 * @property OneHasMany|Message[] $messages {1:m Message::$team}
 * @property OneHasMany|Token[] $tokens {1:m Token::$team}
 * @property Invoice $lastInvoice {virtual}
 */
class Team extends Entity {
	public const REGISTERED = 'registered';
	public const PAID = 'paid';
	public const WITHDRAWN = 'withdrawn';

	public function getJsonData(): \stdClass {
		$data = Json::decode($this->details);
		\assert($data instanceof \stdClass); // For PHPStan

		return $data;
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

	/**
	 * @param Message::STATUS_* $status
	 *
	 * @return ICollection<Message>
	 */
	public function getMessagesByStatus(string $status): ICollection {
		return $this->messages->toCollection()->findBy(['status' => $status]);
	}
}
