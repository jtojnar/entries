<?php

declare(strict_types=1);

namespace App\Model\Orm\Country;

use Nextras\Orm\Entity\Entity;

/**
 * Country.
 *
 * @property int $id {primary}
 * @property string $name
 * @property string $codeIoc
 * @property string $codeAlpha2
 * @property string $aliases
 * @property bool $europe
 */
final class Country extends Entity {
}
