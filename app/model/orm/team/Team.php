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
 * @property string $status {default self::STATUS_REGISTERED} {enum self::STATUS_*}
 * @property \DateTimeImmutable $timestamp {default now}
 * @property array $jsonData {virtual}
 * @property string $details
 * @property string $ip
 * @property string $password
 * @property OneHasMany<Person> $persons {1:m Person::$team}
 * @property OneHasMany<Invoice> $invoices {1:m Invoice::$team}
 * @property OneHasMany<Message> $messages {1:m Message::$team}
 * @property OneHasMany<Token> $tokens {1:m Token::$team}
 * @property Invoice $lastInvoice {virtual}
 *
 * @phpstan-property self::STATUS_* $status
 */
final class Team extends Entity {
	public const STATUS_REGISTERED = 'registered';
	public const STATUS_PAID = 'paid';
	public const STATUS_WITHDRAWN = 'withdrawn';

	public function getJsonData(): \stdClass {
		$data = Json::decode($this->details);
		\assert($data instanceof \stdClass); // For PHPStan

		return $data;
	}

	public function setJsonData(array|\stdClass $data): void {
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
