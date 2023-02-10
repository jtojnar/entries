<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Configuration\Entries;
use App\Model\Orm\Invoice\Invoice;
use App\Model\Orm\Team\Team;

interface InvoiceModifier {
	public static function modify(Team $team, Invoice $invoice, Entries $parameters): void;
}
