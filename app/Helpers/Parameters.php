<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Model\Configuration\Helpers;
use Rikudou\Iban\Iban\IbanInterface;

final readonly class Parameters {
	public ?IbanInterface $accountNumberIban;

	/**
	 * @param array<mixed> $siteTitle
	 * @param array<mixed> $siteTitleShort
	 * @param array<mixed> $locales
	 */
	public function __construct(
		public string $appDir,
		public string $tempDir,
		/** @var array<string, string> */
		public array $siteTitle,
		/** @var array<string, string> */
		public array $siteTitleShort,
		/**
		 * @var array<string, string>
		 * Array of code => name for supported locales
		 */
		public array $locales,
		/**
		 * The the organizers’ e-mail address.
		 */
		public string $webmasterEmail,
		public ?string $accountNumber,
	) {
		$this->accountNumberIban = Helpers::parseAccountNumber($this->accountNumber);
	}

	/**
	 * Get the event name for the given locale, if defined.
	 */
	public function getSiteTitle(string $locale): ?string {
		return $this->siteTitle[$locale] ?? null;
	}

	/**
	 * Get the short event name for the given locale, if defined.
	 */
	public function getSiteTitleShort(string $locale): ?string {
		return $this->siteTitleShort[$locale] ?? null;
	}

	public function getWebmasterEmail(): string {
		return $this->webmasterEmail;
	}
}
