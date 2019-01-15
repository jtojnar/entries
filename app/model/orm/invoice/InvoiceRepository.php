<?php

declare(strict_types=1);

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class InvoiceRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Invoice::class];
	}
}
