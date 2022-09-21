<?php

declare(strict_types=1);

namespace App\Model;

use Nextras\Dbal\Result\Result;

final class ItemReservationMapper extends BaseMapper {
	public function getStats(): Result {
		$query = $this->builder()->select('name')->addSelect('COUNT(*) AS cnt')->groupBy('name')->getQuerySql();

		return $this->connection->queryArgs($query);
	}
}
