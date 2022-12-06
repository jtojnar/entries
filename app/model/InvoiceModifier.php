<?php

declare(strict_types=1);

namespace App\Model;

interface InvoiceModifier {
	public static function modify(Team $team, Invoice $invoice, array $parameters): void;
}
