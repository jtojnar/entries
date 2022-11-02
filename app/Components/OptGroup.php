<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Components;

use App\Locale\Translated;
use Contributte\Translation\Wrappers\NotTranslate;

final class OptGroup {
	public function __construct(
		public readonly string|Translated|NotTranslate $label,
		/** @var array<string, string|Translated|NotTranslate> */
		public readonly array $options,
	) {
	}
}
