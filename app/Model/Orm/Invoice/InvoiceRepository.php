<?php

declare(strict_types=1);

namespace App\Model\Orm\Invoice;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Invoice>
 */
final class InvoiceRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Invoice::class];
	}
}
