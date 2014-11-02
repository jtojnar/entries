<?php

namespace App\Model;

use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Country
 * @property string $name
 * @property string $code
 * @property string $aliases
 * @property bool $europe
 *
 * @property OneHasMany|Person[] $persons {1:m PersonRepository $country}
 */
class Country extends Entity {
}
