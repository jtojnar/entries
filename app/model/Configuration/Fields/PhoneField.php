<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Fields;

class PhoneField extends Field {
	public function getType(): string {
		return 'phone';
	}
}
