<?php

declare(strict_types=1);

namespace App\Model\Orm\Invoice;

use Nextras\Orm\Repository\Repository;

final class InvoiceRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Invoice::class];
	}
}
