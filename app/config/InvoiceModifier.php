<?php

declare(strict_types=1);

namespace App;

use App\Model\Invoice;
use App\Model\InvoiceItem;
use App\Model\Team;
use Money\Money;
use Nette;

class InvoiceModifier {
	use Nette\SmartObject;

	public static function modify(Team $team, Invoice $invoice, array $parameters): void {
		$eventDate = $parameters['eventDate'];

		if ($team->category === 'MJ' || $team->category === 'WJ' || $team->category === 'XJ') {
			self::adjustJuniorStagePrices($invoice);
		}

		self::fixPersonItemAmounts($invoice, \count($team->persons));
	}

	private static function adjustJuniorStagePrices(Invoice $invoice): void {
		$items = $invoice->items;

		if (isset($items['team:enum:friday2h:yes'])) {
			$items['team:enum:friday2h:yes'] = self::discount($items['team:enum:friday2h:yes'], 20);
		}

		if (isset($items['team:enum:saturday5h:yes'])) {
			$items['team:enum:saturday5h:yes'] = self::discount($items['team:enum:saturday5h:yes'], 20);
		}

		if (isset($items['team:enum:sunday4h:yes'])) {
			$items['team:enum:sunday4h:yes'] = self::discount($items['team:enum:sunday4h:yes'], 20);
		}

		$invoice->items = $items;
	}

	private static function fixPersonItemAmounts(Invoice $invoice, int $personCount): void {
		$items = $invoice->items;

		if (isset($items['team:enum:friday2h:yes'])) {
			$items['team:enum:friday2h:yes'] = $items['team:enum:friday2h:yes']->setAmount($personCount);
		}

		if (isset($items['team:enum:saturday5h:yes'])) {
			$items['team:enum:saturday5h:yes'] = $items['team:enum:saturday5h:yes']->setAmount($personCount);
		}

		if (isset($items['team:enum:sunday4h:yes'])) {
			$items['team:enum:sunday4h:yes'] = $items['team:enum:sunday4h:yes']->setAmount($personCount);
		}

		if (isset($items['all_stages_discount'])) {
			$items['all_stages_discount'] = $items['all_stages_discount']->setAmount($personCount);
		}

		$invoice->items = $items;
	}

	private static function discount(InvoiceItem $item, int $discount): InvoiceItem {
		return $item->setPrice($item->price->subtract(Money::CZK($discount * 100))); // price in halíř
	}
}
