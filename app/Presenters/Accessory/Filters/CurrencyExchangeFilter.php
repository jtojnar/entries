<?php

declare(strict_types=1);

namespace App\Presenters\Accessory\Filters;

use Money\Converter;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Exchange;
use Money\Money;

final class CurrencyExchangeFilter {
	public function __construct(
		public Exchange $exchange,
	) {
	}

	/**
	 * @param non-empty-string|Currency $targetCurrency
	 */
	public function __invoke(Money $money, string|Currency $targetCurrency): Money {
		if (\is_string($targetCurrency)) {
			$targetCurrency = new Currency($targetCurrency);
		}

		$converter = new Converter(new ISOCurrencies(), $this->exchange);

		return $converter->convert($money, $targetCurrency);
	}
}
