<?php

declare(strict_types=1);

namespace App\Model;

use Nextras\Orm\Entity\Entity;

/**
 * Item reservation for enforcing limits.
 *
 * @property int $id {primary}
 * @property string $name
 * @property ?Team $team {m:1 Team::$itemReservations}
 * @property ?Person $person {m:1 Person::$itemReservations}
 */
final class ItemReservation extends Entity {
	public function __construct(string $name) {
		parent::__construct();

		$this->name = $name;
	}
}
