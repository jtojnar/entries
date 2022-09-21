<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Configuration\Entries;

interface InvoiceModifier {
	public static function modify(Team $team, Invoice $invoice, Entries $parameters): void;
}
