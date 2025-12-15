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

	public static function fromRoot(mixed $fees): self {
		$fees = Helpers::ensureArray('fees', $fees);

		$currencyCode = Helpers::ensureNonEmptyString('fees.currency', $fees['currency'] ?? 'CZK');
		$currency = new Currency($currencyCode);

		return new self(
			currency: $currency,
			person: self::parsePersonFee('fees', $fees, $currency),
		);
	}

	public static function from(string $context, mixed $fees, self $parentFees): self {
		$fees = Helpers::ensureArray("$context.fees", $fees);

		$currency = isset($fees['currency']) ? new Currency(Helpers::ensureNonEmptyString("$context.fees.currency", $fees['currency'])) : $parentFees->currency;

		return new self(
			currency: $currency,
			person: self::parsePersonFee("$context.fees", $fees, $currency) ?? $parentFees->person,
		);
	}

	/**
	 * @param array<mixed> $fees
	 */
	private static function parsePersonFee(string $context, array $fees, Currency $currency): ?Money {
		if (!isset($fees['person'])) {
			return null;
		}

		$personFee = $fees['person'];

		if (!\is_int($personFee)) {
			throw new InvalidConfigurationException("$context.person must be an integer");
		}

		return new Money($personFee * 100, $currency);
	}
}
