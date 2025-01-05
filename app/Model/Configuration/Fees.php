<?php

declare(strict_types=1);

namespace App\Model\Configuration;

use Money\Currency;
use Money\Money;

final class Fees {
	public function __construct(
		public readonly Currency $currency,
		public readonly ?Money $person,
	) {
	}

	/**
	 * @param array<mixed> $fees
	 */
	public static function fromRoot(array $fees): self {
		$currencyCode = Helpers::ensureNonEmptyString('fees.currency', $fees['currency'] ?? 'CZK');
		$currency = new Currency($currencyCode);

		return new self(
			currency: $currency,
			person: isset($fees['person']) ? new Money($fees['person'] * 100, $currency) : null,
		);
	}

	/**
	 * @param array<mixed> $fees
	 */
	public static function from(string $context, array $fees, self $parentFees): self {
		$currency = isset($fees['currency']) ? new Currency(Helpers::ensureNonEmptyString("$context.fees.currency", $fees['currency'])) : $parentFees->currency;

		return new self(
			currency: $currency,
			person: isset($fees['person']) ? new Money($fees['person'] * 100, $currency) : $parentFees->person,
		);
	}
}
