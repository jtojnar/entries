<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Exceptions\LimitedAccessException;
use Nette;
use Nette\Application\Attributes\Requires;

/**
 * Handles 4xx HTTP error responses.
 *
 * @property Nette\Application\UI\Template $template
 */
#[Requires(forward: true)]
final class Error4xxPresenter extends BasePresenter {
	public function renderDefault(Nette\Application\BadRequestException $exception): void {
		if ($exception instanceof LimitedAccessException) {
			$this->forward('ErrorAccess:default', ['exception' => $exception]);
		}

		// renders the appropriate error template based on the HTTP status code
		$code = $exception->getCode();
		$file = is_file($file = __DIR__ . "/templates/Error/$code.latte")
			? $file
			: __DIR__ . '/templates/Error/4xx.latte';
		$this->template->httpCode = $code;
		$this->template->setFile($file);
	}
}
