<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2023 Jan Tojnar

declare(strict_types=1);

namespace App\Helpers;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;

final class EmailFactory {
	public function __construct(
		private string $appDir,
	) {
	}

	public function create(string $mailHtml): string {
		$css = @file_get_contents($this->appDir . '/Templates/Mail/style.css');
		\assert($css !== false, 'E-mail stylesheet must be readable');
		$domDocument = CssInliner::fromHtml($mailHtml)
			->inlineCss($css)
			->getDomDocument();
		HtmlPruner::fromDomDocument($domDocument)
			->removeElementsWithDisplayNone();
		$mailHtml = CssToAttributeConverter::fromDomDocument($domDocument)
			->convertCssToVisualAttributes()
			->render();

		return $mailHtml;
	}
}
