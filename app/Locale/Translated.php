<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Locale;

interface Translated {
	public function getMessage(string $locale): string;
}
