<?php

declare(strict_types=1);

namespace App\Model;

use Nextras\Orm\Entity\Entity;

/**
 * Message.
 *
 * @property int $id {primary}
 * @property string $status {default self::STATUS_QUEUED} {enum self::STATUS_*}
 * @property \DateTimeImmutable $timestamp {default now}
 * @property Team $team {m:1 Team::$messages}
 * @property string $subject
 * @property string $sender
 * @property string $body
 *
 * @property-phpstan self::STATUS_* $status
 */
final class Message extends Entity {
	public const STATUS_QUEUED = 'queued';
	public const STATUS_CANCELLED = 'cancelled';
	public const STATUS_SENT = 'sent';
}
