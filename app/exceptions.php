<?php

namespace App;

class LimitedAccessException extends Nette\Application\BadRequestException {
	const SOON = 400;
	const LATE = 401;
	public function __construct($code) {
		if ($code != self::SOON) {
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
