<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\Attributes\Requires;
use Nette\Application\Responses;
use Nette\Http;
use Override;
use Tracy\ILogger;

/**
 * Handles uncaught exceptions and errors, and logs them.
 */
#[Requires(forward: true)]
final readonly class Error5xxPresenter implements Nette\Application\IPresenter {
	public function __construct(
		private ILogger $logger,
	) {
	}

	#[Override]
	public function run(Nette\Application\Request $request): Nette\Application\Response {
		$exception = $request->getParameter('exception');

		// Log the exception and display a generic error message to the user
		$this->logger->log($exception, ILogger::EXCEPTION);

		return new Responses\CallbackResponse(function(Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {
			if (preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type')) === 1) {
				require __DIR__ . '/templates/Error/500.phtml';
			}
		});
	}
}
