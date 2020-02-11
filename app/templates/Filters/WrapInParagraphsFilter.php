<?php

declare(strict_types=1);

namespace App\Templates\Filters;

class WrapInParagraphsFilter {
	public function __invoke(array $arr): string {
		return implode('', array_map(function($e) {
			return '<p>' . $e . '</p>';
		}, $arr));
	}
}
