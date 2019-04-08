<?php

declare(strict_types=1);

namespace App;

use App\Model\Invoice;
use App\Model\Team;
use App\Presenters\TeamPresenter;
use Nette;

class InvoiceModifier {
	use Nette\SmartObject;

	public static function modify(Team $team, Invoice $invoice, array $parameters): void {
		$eventDate = $parameters['eventDate'];

		if ($team->category === 'RD') {
			$numberOfChildren = \count(array_filter(iterator_to_array($team->persons), function($person) use ($eventDate) {
				$age = $person->birth->diff($eventDate, true)->y;

				return $age < 15;
			}));

			$personEntryFeeItemName = TeamPresenter::serializeInvoiceItem([
				'type' => '~entry',
				'scope' => 'person',
			]);

			$items = $invoice->items;

			$items[$personEntryFeeItemName]['amount'] -= $numberOfChildren;

			$invoice->items = $items;
		}
	}
}
