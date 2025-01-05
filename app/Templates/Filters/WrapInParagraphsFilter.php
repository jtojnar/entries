<?php

declare(strict_types=1);

namespace App\Templates\Filters;

final class WrapInParagraphsFilter {
	/**
	 * @param list<string> $arr
	 */
	public function __invoke(array $arr): string {
		return implode(
			'',
			array_map(
				fn($e): string => '<p>' . $e . '</p>',
				$arr
			)
		);
	}
}
