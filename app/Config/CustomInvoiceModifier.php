<?php

declare(strict_types=1);

namespace App\Config;

use App\Model\Configuration\Entries;
use App\Model\InvoiceModifier;
use App\Model\Orm\Invoice\Invoice;
use App\Model\Orm\Team\Team;
use Money\Money;
use Override;

final class CustomInvoiceModifier implements InvoiceModifier {
	#[Override]
	public static function modify(Team $team, Invoice $invoice, Entries $entries): void {
		$eventDate = $entries->eventDate;

		$data = $team->getJsonData();
		if ($data->saturday5h === 'yes' && $data->sunday4h === 'yes') {
			$invoice->addItem('all_stages_discount', Money::CZK(-50_00)); // price in halíř
		}

		self::fixPersonItemAmounts($invoice, \count($team->persons));
	}

	private static function fixPersonItemAmounts(Invoice $invoice, int $personCount): void {
		$items = $invoice->items;

		if (isset($items['all_stages_discount'])) {
			$items['all_stages_discount'] = $items['all_stages_discount']->withAmount($personCount);
		}

		$invoice->items = $items;
	}
}
