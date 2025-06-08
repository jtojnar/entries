<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

/**
 * Handles 4xx HTTP error responses.
 *
 * @property Nette\Application\UI\Template $template
 */
final class Error4xxPresenter extends BasePresenter {
	protected function checkHttpMethod(): void {
		// allow access via all HTTP methods and ensure the request is a forward (internal redirect)
		if ($this->getRequest() === null || !$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
			$this->error();
		}
	}

	public function renderDefault(Nette\Application\BadRequestException $exception): void {
		// renders the appropriate error template based on the HTTP status code
		$code = $exception->getCode();
		$file = is_file($file = __DIR__ . "/../Templates/Error/$code.latte")
			? $file
			: __DIR__ . '/../Templates/Error/4xx.latte';
		$this->template->httpCode = $code;
		$this->template->setFile($file);
	}
}
