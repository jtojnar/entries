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

	public static function from(array $fees, ?self $parentFees = null): self {
		$currency = $parentFees?->currency ?? new Currency($fees['currency'] ?? 'CZK');

		return new self(
			currency: $currency,
			person: isset($fees['person']) ? new Money($fees['person'] * 100, $currency) : $parentFees?->person,
		);
	}
}
