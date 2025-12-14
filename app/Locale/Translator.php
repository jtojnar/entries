<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Locale;

use Contributte\Translation\Translator as ContributeTranslator;
use Override;

class Translator extends ContributeTranslator {
	#[Override]
	public function translate(
		$message,
		...$parameters,
	): string {
		if ($message instanceof Translated) {
			return $message->getMessage($this->getLocale());
		}

		return parent::translate($message, ...$parameters);
	}
}
