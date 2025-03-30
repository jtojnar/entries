<?php

declare(strict_types=1);

namespace App\Config;

use App\Model\Configuration\Entries;
use App\Model\InvoiceModifier;
use App\Model\Orm\Invoice\Invoice;
use App\Model\Orm\Team\Team;
use Money\Money;

final class CustomInvoiceModifier implements InvoiceModifier {
	public static function modify(Team $team, Invoice $invoice, Entries $entries): void {
		if (!str_ends_with($team->category, '6')) {
			// Tent is free for 12 and 24 hour categories.
			self::adjustTentPrice($invoice);
		}

		self::fixPersonItemAmounts($invoice, \count($team->persons));
	}

	private static function adjustTentPrice(Invoice $invoice): void {
		$items = $invoice->items;

		if (isset($items['team:checkbox:tent:'])) {
			$items['team:checkbox:tent:'] = $items['team:checkbox:tent:']->setPrice(Money::CZK(0));
		}

		$invoice->items = $items;
	}

	private static function fixPersonItemAmounts(Invoice $invoice, int $personCount): void {
		$items = $invoice->items;

		if (isset($items['team:checkbox:tent:'])) {
			$items['team:checkbox:tent:'] = $items['team:checkbox:tent:']->setAmount($personCount);
		}

		$invoice->items = $items;
	}
}
