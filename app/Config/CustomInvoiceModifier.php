<?php

declare(strict_types=1);

namespace App\Config;

use App\Model\Configuration\Entries;
use App\Model\InvoiceModifier;
use App\Model\Orm\Invoice\Invoice;
use App\Model\Orm\Invoice\InvoiceItem;
use App\Model\Orm\Team\Team;
use Money\Money;

final class CustomInvoiceModifier implements InvoiceModifier {
	public static function modify(Team $team, Invoice $invoice, Entries $entries): void {
		$eventDate = $entries->eventDate;

		if ($team->category === 'MJ' || $team->category === 'WJ' || $team->category === 'XJ') {
			self::adjustJuniorStagePrices($invoice);
		}

		self::fixPersonItemAmounts($invoice, \count($team->persons));
	}

	private static function adjustJuniorStagePrices(Invoice $invoice): void {
		$items = $invoice->items;

		if (isset($items['team:enum:saturday5h:yes'])) {
			$items['team:enum:saturday5h:yes'] = self::discount($items['team:enum:saturday5h:yes'], 10);
		}

		if (isset($items['team:enum:sunday4h:yes'])) {
			$items['team:enum:sunday4h:yes'] = self::discount($items['team:enum:sunday4h:yes'], 10);
		}

		$invoice->items = $items;
	}

	private static function fixPersonItemAmounts(Invoice $invoice, int $personCount): void {
		$items = $invoice->items;

		if (isset($items['team:enum:friday2h:yes'])) {
			$items['team:enum:friday2h:yes'] = $items['team:enum:friday2h:yes']->withAmount($personCount);
		}

		if (isset($items['team:enum:saturday5h:yes'])) {
			$items['team:enum:saturday5h:yes'] = $items['team:enum:saturday5h:yes']->withAmount($personCount);
		}

		if (isset($items['team:enum:sunday4h:yes'])) {
			$items['team:enum:sunday4h:yes'] = $items['team:enum:sunday4h:yes']->withAmount($personCount);
		}

		if (isset($items['all_stages_discount'])) {
			$items['all_stages_discount'] = $items['all_stages_discount']->withAmount($personCount);
		}

		$invoice->items = $items;
	}

	private static function discount(InvoiceItem $item, int $discount): InvoiceItem {
		return $item->withPrice($item->getPrice()->subtract(Money::CZK($discount * 100))); // price in halíř
	}
}
