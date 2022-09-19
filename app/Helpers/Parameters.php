<?php

declare(strict_types=1);

namespace App\Helpers;

class Parameters {
	public function __construct(private array $parameters) {
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
}
