<?php

declare(strict_types=1);

namespace App\Config;

use App\Model\Configuration\Entries;
use App\Model\InvoiceModifier;
use App\Model\Orm\Invoice\Invoice;
use App\Model\Orm\Invoice\InvoiceItem;
use App\Model\Orm\Team\Team;
use Money\Money;
use Override;

final class CustomInvoiceModifier implements InvoiceModifier {
	#[Override]
	public static function modify(Team $team, Invoice $invoice, Entries $entries): void {
		$eventDate = $entries->eventDate;

		if ($team->category === 'MJ' || $team->category === 'WJ' || $team->category === 'XJ') {
			self::adjustJuniorStagePrices($invoice);
		}

		$data = $team->getJsonData();
		if ($data->friday2h === 'yes' && $data->saturday5h === 'yes' && $data->sunday4h === 'yes') {
			$invoice->addItem('all_stages_discount', $invoice->items['team:enum:friday2h:yes']->getPrice()->multiply(-1));
		}

		self::fixPersonItemAmounts($invoice, \count($team->persons));
	}

	private static function adjustJuniorStagePrices(Invoice $invoice): void {
		$items = $invoice->items;

		if (isset($items['team:enum:friday2h:yes'])) {
			$items['team:enum:friday2h:yes'] = self::discount($items['team:enum:friday2h:yes'], 10);
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
