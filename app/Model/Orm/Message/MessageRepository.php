<?php

declare(strict_types=1);

namespace App\Model\Orm\Message;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Message>
 */
final class MessageRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Message::class];
	}
}
