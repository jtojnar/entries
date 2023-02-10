<?php

declare(strict_types=1);

namespace App\Exceptions;

use Nette;

class LimitedAccessException extends Nette\Application\BadRequestException {
	public const SOON = 409;
	public const LATE = 410;

	public function __construct(int $code) {
		if ($code !== self::SOON) {
			$code = self::LATE;
		}
		parent::__construct('', $code);
	}
}
