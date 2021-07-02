<?php

declare(strict_types=1);

namespace App\Presenters;

use App\LimitedAccessException;
use Exception;
use Nette;
use Tracy\ILogger;

/**
 * Presenter for handling errors.
 */
class ErrorPresenter extends BasePresenter {
	/** @var ILogger @inject */
	private $logger;

	public function renderDefault(Exception $exception): void {
		if ($exception instanceof LimitedAccessException) {
			$code = $exception->getCode();
			$errorType = $code === LimitedAccessException::LATE ? 'late' : 'early';

			$this->setView('access');

			$fmt = $this->translator->translate('messages.date');
			$this->template->openingDate = $this->context->parameters['entries']['opening']->format($fmt);

			$this->template->errorType = $errorType;
		} elseif ($exception instanceof Nette\Application\BadRequestException) {
			$code = $exception->getCode();
			// load template 403.latte or 404.latte or ... 4xx.latte
			$this->setView(\in_array($code, [403, 404, 405, 410, 500], true) ? (string) $code : '4xx');
			// log to access.log
			$this->logger->log("HTTP code {$exception->getCode()}: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');
		} else {
			$this->setView('500'); // load template 500.latte
			if (isset($exception)) {
				$this->logger->log($exception);
			}
		}

		if ($this->isAjax()) { // AJAX request? Note this error in payload.
			$this->payload->error = true;
			$this->terminate();
		}
	}
}
