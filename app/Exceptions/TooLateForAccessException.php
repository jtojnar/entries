<?php

declare(strict_types=1);

namespace App\Exceptions;

final class TooLateForAccessException extends LimitedAccessException {
	public function __construct() {
		parent::__construct(self::LATE);
	}
}
