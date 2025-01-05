<?php

declare(strict_types=1);

namespace App\Model\Configuration;

use Money\Currency;
use Money\Money;

final readonly class Fees {
	public function __construct(
		public Currency $currency,
		public ?Money $person,
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

	public static function from(array $fees, ?self $parentFees = null): self {
		$currency = isset($fees['currency']) ? new Currency($fees['currency']) : $parentFees?->currency;

		return new self(
			currency: $currency,
			person: isset($fees['person']) ? new Money($fees['person'] * 100, $currency) : $parentFees?->person,
		);
	}
}
