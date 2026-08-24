<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Exceptions\LimitedAccessException;
use Nette;
use Nette\Application\Responses;
use Nette\Http;
use Override;
use Tracy\ILogger;

/**
 * Handles uncaught exceptions and errors, and logs them.
 */
final readonly class ErrorPresenter implements Nette\Application\IPresenter {
	public function __construct(
		private ILogger $logger,
	) {
	}

	#[Override]
	public function run(Nette\Application\Request $request): Nette\Application\Response {
		$exception = $request->getParameter('exception');

		if ($exception instanceof LimitedAccessException) {
			[$module, , $sep] = Nette\Application\Helpers::splitName($request->getPresenterName());
			$errorPresenter = $module . $sep . 'ErrorAccess';

			return new Responses\ForwardResponse($request->setPresenterName($errorPresenter));
		}

		// If the exception is a 4xx HTTP error, forward to the Error4xxPresenter
		if ($exception instanceof Nette\Application\BadRequestException) {
			[$module, , $sep] = Nette\Application\Helpers::splitName($request->getPresenterName());
			$errorPresenter = $module . $sep . 'Error4xx';

			return new Responses\ForwardResponse($request->setPresenterName($errorPresenter));
		}

		// Log the exception and display a generic error message to the user
		$this->logger->log($exception, ILogger::EXCEPTION);

		return new Responses\CallbackResponse(function(Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {
			if (preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type')) === 1) {
				require __DIR__ . '/templates/Error/500.phtml';
			}
		});
	}
}
