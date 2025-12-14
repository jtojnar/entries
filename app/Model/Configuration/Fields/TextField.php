<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration\Fields;

use Override;

class TextField extends Field {
	#[Override]
	public function getType(): string {
		return 'text';
	}
}
