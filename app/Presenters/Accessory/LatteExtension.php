<?php

declare(strict_types=1);

namespace App\Presenters\Accessory;

use Latte\Extension;
use Override;

final class LatteExtension extends Extension {
	public function __construct(
		private Filters\CategoryFormatFilter $categoryFormatFilter,
		private Filters\CurrencyExchangeFilter $currencyExchangeFilter,
		private Filters\PriceFilter $priceFilter,
		private Filters\WrapInParagraphsFilter $wrapInParagraphsFilter,
	) {
	}

	#[Override]
	public function getFilters(): array {
		return [
			'categoryFormat' => $this->categoryFormatFilter,
			'exchangeCurrency' => $this->currencyExchangeFilter,
			'price' => $this->priceFilter,
			'wrapInParagraphs' => $this->wrapInParagraphsFilter,
		];
	}
}
