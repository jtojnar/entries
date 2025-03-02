<?php

declare(strict_types=1);

namespace App\Model\Orm\Token;

use App\Model\Orm\Team\Team;
use DateTimeImmutable;
use Nextras\Orm\Entity\Entity;

/**
 * Authentication token.
 *
 * @property int $id {primary}
 * @property DateTimeImmutable $timestamp {default now}
 * @property Team $team {m:1 Team::$tokens}
 * @property string $hash
 */
final class Token extends Entity {
}
