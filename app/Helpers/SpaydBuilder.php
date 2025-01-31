<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2025 Jan Tojnar

declare(strict_types=1);

namespace App\Helpers;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Nette\Utils\Strings;
use Rikudou\Iban\Iban\IbanInterface;

/**
 * Builds a Short Payment Descriptor string.
 *
 * @see https://en.wikipedia.org/wiki/Short_Payment_Descriptor
 * @see https://qr-platba.cz/
 */
final readonly class SpaydBuilder {
	/**
	 * Generates payment instructions in <> format.
	 */
	public static function make(
		IbanInterface $accountNumber,
		Money $amount,
		?string $message = null,
		?string $variableSymbol = null,
	): string {
		return 'SPD*1.0*'
			. self::keyVal('ACC', $accountNumber->asString())
			. self::amount($amount)
			. ($message !== null ? self::message($message) : '')
			. ($variableSymbol !== null ? self::keyVal('X-VS', $variableSymbol) : '');
	}

	private static function amount(Money $amount): string {
		$currencies = new ISOCurrencies();
		$moneyFormatter = new DecimalMoneyFormatter($currencies);
		$value = $moneyFormatter->format($amount);

		return self::keyVal('AM', $value);
	}

	/** For simplicity, we are going to convert the string to ASCII and get rid of all special characters. */
	private static function message(string $message): string {
		$message = Strings::webalize($message, ' ');
		$message = trim($message);

		return self::keyVal('MSG', $message);
	}

	private static function keyVal(string $key, string $value): string {
		return $key . ':' . self::escapeValue($value) . '*';
	}

	/** Escapes a value part of key-value pair. */
	private static function escapeValue(string $value): string {
		$value = trim($value);
		$value = str_replace('*', '%2A', $value);

		return $value;
	}
}
