<?php

declare(strict_types=1);

namespace App;

use Nette;

class LimitedAccessException extends Nette\Application\BadRequestException {
	public const SOON = 409;
	public const LATE = 410;

	public function __construct($code) {
		if ($code !== self::SOON) {
			$code = self::LATE;
		}
		parent::__construct('', $code);
	}
}

class TooSoonForAccessException extends LimitedAccessException {
	public function __construct() {
		parent::__construct(self::SOON);
	}
}

class TooLateForAccessException extends LimitedAccessException {
	public function __construct() {
		parent::__construct(self::LATE);
	}
}
