<?php

declare(strict_types=1);

namespace App\Model\Orm\ItemReservation;

use App\Model\Orm\BaseMapper;
use Nextras\Dbal\Result\Result;

/**
 * @extends BaseMapper<ItemReservation>
 */
final class ItemReservationMapper extends BaseMapper {
	public function getStats(): Result {
		$builder = $this->builder()->select('name')->addSelect('COUNT(*) AS cnt')->groupBy('name');

		return $this->connection->queryByQueryBuilder($builder);
	}
}
