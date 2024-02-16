<?php

declare(strict_types=1);

namespace App\Helpers;

final class Parameters {
	public function __construct(
		private readonly array $parameters,
	) {
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
