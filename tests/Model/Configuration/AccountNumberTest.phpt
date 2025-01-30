<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2025 Jan Tojnar

declare(strict_types=1);

namespace App\Tests\Model\Configuration;

use App\Model\Configuration\Helpers;
use App\Model\Configuration\InvalidConfigurationException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class AccountNumberTest extends TestCase {
	public function testNull(): void {
		$iban = Helpers::parseAccountNumber(null);
		Assert::same(null, $iban);
	}

	public function testCzech(): void {
		$iban = Helpers::parseAccountNumber('281115217/0300');
		Assert::same('CZ9103000000000281115217', $iban?->asString());
	}

	public function testCzechWithPrefix(): void {
		$iban = Helpers::parseAccountNumber('21012-27924051/0710');
		Assert::same('CZ4707100210120027924051', $iban?->asString());
	}

	public function testCzechInvalid(): void {
		Assert::exception(
			function(): void {
				Helpers::parseAccountNumber('123456/1234');
			},
			InvalidConfigurationException::class,
			'Invalid account number.',
		);
	}

	public function testIbanCzech(): void {
		$iban = Helpers::parseAccountNumber('CZ5530300000001325090010');
		Assert::same('CZ5530300000001325090010', $iban?->asString());
	}

	public function testIbanHungarian(): void {
		$iban = Helpers::parseAccountNumber('HU38109180010000011721150000');
		Assert::same('HU38109180010000011721150000', $iban?->asString());
	}

	public function testIbanInvalid(): void {
		Assert::exception(
			function(): void {
				Helpers::parseAccountNumber('CZ1327000000000500114005');
			},
			InvalidConfigurationException::class,
			'Invalid account number.',
		);
	}

	public function testInvalid(): void {
		Assert::exception(
			function(): void {
				Helpers::parseAccountNumber('foo');
			},
			InvalidConfigurationException::class,
			'Invalid account number.',
		);
	}
}

(new AccountNumberTest())->run();
