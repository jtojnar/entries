<?php

declare(strict_types=1);

namespace App\Presenters\Accessory;

use Latte\Extension;
use Override;

final class LatteExtension extends Extension {
	public function __construct(
		private readonly Filters\CategoryFormatFilter $categoryFormatFilter,
		private readonly Filters\CurrencyExchangeFilter $currencyExchangeFilter,
		private readonly Filters\PriceFilter $priceFilter,
		private readonly Filters\WrapInParagraphsFilter $wrapInParagraphsFilter,
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
