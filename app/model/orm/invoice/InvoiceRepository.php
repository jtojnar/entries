<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class InvoiceRepository extends Repository {
	public static function getEntityClassNames() {
		return [Invoice::class];
	}
}
