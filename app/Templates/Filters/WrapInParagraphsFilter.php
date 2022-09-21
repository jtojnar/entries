<?php

declare(strict_types=1);

namespace App\Templates\Filters;

final class WrapInParagraphsFilter {
	public function __invoke(array $arr): string {
		return implode(
			'',
			array_map(
				fn($e) => '<p>' . $e . '</p>',
				$arr
			)
		);
	}
}
