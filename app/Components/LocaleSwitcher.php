<?php

declare(strict_types=1);

namespace App\Components;

use Nette\Application\UI\Control;

/**
 * Control for switching locale of the application.
 */
final class LocaleSwitcher extends Control {
	/** @var array<string, string> */
	private array $locales;

	/**
	 * @param array<string, string> $locales
	 * @param array<string> $allowedLocales
	 */
	public function __construct(array $locales, ?array $allowedLocales) {
		if ($allowedLocales === null) {
			$this->locales = $locales;
		} else {
			$this->locales = array_filter(
				$locales,
				fn(string $code): bool => \in_array($code, $allowedLocales, true),
				\ARRAY_FILTER_USE_KEY
			);
		}
	}

	public function render(): void {
		/** @var \Nette\Bridges\ApplicationLatte\DefaultTemplate */
		$template = $this->template;

		$template->locales = $this->locales;

		$template->render(__DIR__ . '/LocaleSwitcher.latte');
	}
}
