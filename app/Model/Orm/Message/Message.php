<?php

declare(strict_types=1);

namespace App\Model\Orm\Message;

use App\Model\Orm\Team\Team;
use DateTimeImmutable;
use Nextras\Orm\Entity\Entity;

/**
 * Message.
 *
 * @property int $id {primary}
 * @property string $status {default self::STATUS_QUEUED} {enum self::STATUS_*}
 * @property DateTimeImmutable $timestamp {default now}
 * @property Team $team {m:1 Team::$messages}
 * @property string $subject
 * @property string $sender
 * @property string $body
 *
 * @property-phpstan self::STATUS_* $status
 */
final class Message extends Entity {
	public const string STATUS_QUEUED = 'queued';
	public const string STATUS_CANCELLED = 'cancelled';
	public const string STATUS_SENT = 'sent';
}
