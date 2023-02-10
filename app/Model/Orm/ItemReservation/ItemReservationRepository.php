<?php

declare(strict_types=1);

namespace App\Model\Orm\ItemReservation;

use Nextras\Orm\Repository\Repository;

/**
 * @property ItemReservationMapper $mapper
 */
final class ItemReservationRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [ItemReservation::class];
	}

	/**
	 * @return array<string, int>
	 */
	public function getStats(): array {
		/** @var array<string, int> */
		$result = $this->mapper->getStats()->fetchPairs('name', 'cnt');

		return $result;
	}
}
