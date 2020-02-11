<?php

declare(strict_types=1);

namespace App\Templates\Filters;

use Nette\Localization\ITranslator;

class PriceFilter {
	/** @var string */
	public $currency;

	/** @var ITranslator */
	public $translator;

	public function __construct(string $currency, ITranslator $translator) {
		$this->currency = $currency;
		$this->translator = $translator;
	}

	public function __invoke(int $amount): string {
		$key = 'messages.currencies.' . $this->currency;
		$translated = $this->translator->translate($key, ['amount' => $amount]);

		return $translated === $key ? (string) $amount : $translated;
	}
}
