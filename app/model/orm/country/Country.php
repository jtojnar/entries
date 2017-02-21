<?php

namespace App\Model;

use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Country
 *
 * @property int $id {primary}
 * @property string $name
 * @property string $code
 * @property string $aliases
 * @property bool $europe
 */
class Country extends Entity {
}
