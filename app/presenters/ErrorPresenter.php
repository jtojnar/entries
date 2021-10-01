<?php

declare(strict_types=1);

namespace App\Presenters;

use App\LimitedAccessException;
use Nette;
use Nette\Application\Responses;
use Nette\Http;
use Tracy\ILogger;

final class ErrorPresenter implements Nette\Application\IPresenter {
	use Nette\SmartObject;

	/**
	 * @var ILogger
	 */
	private $logger;

	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

	public function run(Nette\Application\Request $request): Nette\Application\IResponse {
		$exception = $request->getParameter('exception');

		if ($exception instanceof LimitedAccessException) {
			[$module, , $sep] = Nette\Application\Helpers::splitName($request->getPresenterName());
			$errorPresenter = $module . $sep . 'ErrorAccess';

			return new Responses\ForwardResponse($request->setPresenterName($errorPresenter));
		} elseif ($exception instanceof Nette\Application\BadRequestException) {
			[$module, , $sep] = Nette\Application\Helpers::splitName($request->getPresenterName());
			$errorPresenter = $module . $sep . 'Error4xx';

			return new Responses\ForwardResponse($request->setPresenterName($errorPresenter));
		}

		$this->logger->log($exception, ILogger::EXCEPTION);

		return new Responses\CallbackResponse(function(Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {
			if (preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type'))) {
				require __DIR__ . '/../templates/Error/500.phtml';
			}
		});
	}
}
