<?php

declare(strict_types=1);

namespace App\Presenters\templates\Mail;

use App\Model\Orm\Invoice\Invoice;
use App\Model\Orm\Person\Person;
use App\Model\Orm\Team\Team;
use Nextras\Orm\Relationships\OneHasMany;

final class Message {
	public function __construct(
		public ?string $accountNumber,
		public ?string $accountNumberIban,
		public string $eventName,
		public string $eventNameShort,
		public string $dateFormat,
		public Team $team,
		/** @var OneHasMany<Person> */
		public OneHasMany $people,
		public int $id,
		public string $name,
		public Invoice $invoice,
		public string $organiserMail,
		public string $subject,
		public string $grant,
	) {
	}
}
