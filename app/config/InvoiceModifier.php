<?php

namespace App;

use App\Model\Team;
use App\Model\Invoice;
use Nette;

class InvoiceModifier {
	use Nette\SmartObject;

	public static function modify(Team $team, Invoice $invoice, array $parameters) {
		$eventDate = $parameters['eventDate'];

		if ($team->category === 'MJ' || $team->category === 'WJ' || $team->category === 'XJ') {
			self::adjustJuniorStagePrices($invoice);
		}

		$data = $team->getJsonData();
		if ($data->friday2h === 'yes' && $data->saturday5h === 'yes' && $data->sunday4h === 'yes') {
			$invoice->addItem('all_stages_discount', -$invoice->items['friday2h-yes']['price']);
		}

		self::fixPersonItemAmounts($invoice, count($team->persons));
	}

	private static function adjustJuniorStagePrices(Invoice $invoice) {
		$items = $invoice->items;

		if (isset($items['friday2h-yes'])) {
			$items['friday2h-yes']['price'] -= 10;
		}

		if (isset($items['saturday5h-yes'])) {
			$items['saturday5h-yes']['price'] -= 20;
		}

		if (isset($items['sunday4h-yes'])) {
			$items['sunday4h-yes']['price'] -= 20;
		}

		$invoice->items = $items;
	}

	private static function fixPersonItemAmounts(Invoice $invoice, $personCount) {
		$items = $invoice->items;

		if (isset($items['friday2h-yes'])) {
			$items['friday2h-yes']['amount'] = $personCount;
		}

		if (isset($items['saturday5h-yes'])) {
			$items['saturday5h-yes']['amount'] = $personCount;
		}

		if (isset($items['sunday4h-yes'])) {
			$items['sunday4h-yes']['amount'] = $personCount;
		}

		if (isset($items['all_stages_discount'])) {
			$items['all_stages_discount']['amount'] = $personCount;
		}

		$invoice->items = $items;
	}
}
