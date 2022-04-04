<?php

declare(strict_types=1);

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class MessageRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Message::class];
	}
}
