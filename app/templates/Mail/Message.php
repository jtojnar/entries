<?php

declare(strict_types=1);

namespace App\Templates\Mail;

use App\Model\Invoice;
use App\Model\Person;
use App\Model\Team;
use Nextras\Orm\Relationships\OneHasMany;

final class Message {
	public function __construct(
		public ?string $accountNumber,
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
