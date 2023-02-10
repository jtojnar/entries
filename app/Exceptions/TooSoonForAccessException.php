<?php

declare(strict_types=1);

namespace App\Exceptions;

final class TooSoonForAccessException extends LimitedAccessException {
	public function __construct() {
		parent::__construct(self::SOON);
	}
}
