<?php

declare(strict_types=1);

namespace App\Templates\Filters;

use Contributte\Translation\Translator;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;

class PriceFilter {
	/** @var Translator */
	public $translator;

	public function __construct(Translator $translator) {
		$this->translator = $translator;
	}

	public function __invoke(Money $money): string {
		$currencies = new ISOCurrencies();

		$numberFormatter = new \NumberFormatter($this->translator->getLocale(), \NumberFormatter::CURRENCY);
		$moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

		return $moneyFormatter->format($money);
	}
}
