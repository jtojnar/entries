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

	public static function from(array $fees, ?self $parentFees = null): self {
		$currency = $parentFees?->currency ?? new Currency($fees['currency'] ?? 'CZK');

		return new self(
			currency: $currency,
			person: isset($fees['person']) ? new Money($fees['person'] * 100, $currency) : $parentFees?->person,
		);
	}
}
