<?php

declare(strict_types=1);

namespace App\Templates\Accessory;

use App\Templates\Filters;
use Latte\Extension;

final class LatteExtension extends Extension {
	public function __construct(
		private Filters\CategoryFormatFilter $categoryFormatFilter,
		private Filters\CurrencyExchangeFilter $currencyExchangeFilter,
		private Filters\PriceFilter $priceFilter,
		private Filters\WrapInParagraphsFilter $wrapInParagraphsFilter,
	) {
	}

	public function getFilters(): array {
		return [
			'categoryFormat' => $this->categoryFormatFilter,
			'exchangeCurrency' => $this->currencyExchangeFilter,
			'price' => $this->priceFilter,
			'wrapInParagraphs' => $this->wrapInParagraphsFilter,
		];
	}
}
