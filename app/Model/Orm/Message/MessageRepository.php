<?php

declare(strict_types=1);

namespace App\Model\Orm\Message;

use Nextras\Orm\Repository\Repository;

final class MessageRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Message::class];
	}
}
