<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Model\Configuration\Helpers;
use Rikudou\Iban\Iban\IbanInterface;

final class Parameters {
	public readonly ?string $accountNumber;
	public readonly ?IbanInterface $accountNumberIban;

	public function __construct(
		private readonly array $parameters,
	) {
		$this->accountNumber = $parameters['accountNumber'] ?? null;
		$this->accountNumberIban = Helpers::parseAccountNumber($this->accountNumber);
	}

	/**
	 * Get the the path to the app directory.
	 */
	public function getAppDir(): string {
		return $this->parameters['appDir'];
	}

	/**
	 * Get the the path to the directory for temporary files.
	 */
	public function getTempDir(): string {
		return $this->parameters['tempDir'];
	}

	/**
	 * Get the event name for the given locale, if defined.
	 */
	public function getSiteTitle(string $locale): ?string {
		return $this->parameters['siteTitle'][$locale] ?? null;
	}

	/**
	 * Get the short event name for the given locale, if defined.
	 */
	public function getSiteTitleShort(string $locale): ?string {
		return $this->parameters['siteTitleShort'][$locale] ?? null;
	}

	/**
	 * Get the array of code => name for supported locales.
	 *
	 * @return array<string, string>
	 */
	public function getLocales(): array {
		return $this->parameters['locales'];
	}

	/**
	 * Get the the organizers’ e-mail address.
	 */
	public function getWebmasterEmail(): string {
		return $this->parameters['webmasterEmail'];
	}
}
