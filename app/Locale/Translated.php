<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Locale;

use Exception;
use Override;
use Stringable;

abstract class Translated implements Stringable {
	abstract public function getMessage(string $locale): string;

	#[Override]
	public function __toString(): string {
		// We rely on custom Translator to call getMessage instead.
		throw new Exception(self::class . ' should not be Stringified');
	}
}
