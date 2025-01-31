<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2025 Jan Tojnar

declare(strict_types=1);

namespace App\Helpers;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Money\Money;
use Nette\Utils\FileSystem;
use Rikudou\Iban\Iban\IbanInterface;

/**
 * Generates payment QR code in Short Payment Descriptor format.
 *
 * @see https://en.wikipedia.org/wiki/Short_Payment_Descriptor
 * @see https://qr-platba.cz/
 */
final readonly class SpaydQrGenerator {
	public function __construct(
		private Parameters $parameters,
	) {
	}

	/**
	 * @return string FS path where the generated images will be stored
	 */
	public function getStoragePath(): string {
		return $this->parameters->getTempDir() . '/qrcodes';
	}

	/**
	 * Generates a payment QR code and stores it as PNG file in the storage directory.
	 *
	 * @return string the file name of the generated image
	 */
	public function generate(
		IbanInterface $accountNumber,
		Money $amount,
		string $eventName,
		int $teamId,
	): string {
		$renderer = new GDLibRenderer(400);

		$writer = new Writer($renderer);

		$spd = SpaydBuilder::make(
			accountNumber: $accountNumber,
			amount: $amount,
			message: $eventName,
			variableSymbol: (string) $teamId,
		);

		$directory = $this->getStoragePath();
		$filename = 'payment-' . $teamId . '.png';
		$path = $directory . '/' . $filename;

		FileSystem::createDir($directory);
		$writer->writeFile($spd, $path);

		return $filename;
	}
}
