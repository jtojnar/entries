<?php

declare(strict_types=1);

namespace App\Presenters;

use App\LimitedAccessException;
use App\Model\Configuration\Entries;
use Nette;
use Nette\DI\Attributes\Inject;

/**
 * @property Nette\Application\UI\Template $template
 */
final class ErrorAccessPresenter extends BasePresenter {
	#[Inject]
	public Entries $entries;

	public function startup(): void {
		parent::startup();
		if ($this->getRequest() === null || !$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
			$this->error();
		}
	}

	public function renderDefault(LimitedAccessException $exception): void {
		$code = $exception->getCode();
		$errorType = $code === LimitedAccessException::LATE ? 'late' : 'early';

		$fmt = $this->translator->translate('messages.date');
		\assert($this->entries->opening !== null);
		$this->template->openingDate = $this->entries->opening->format($fmt);

		$this->template->errorType = $errorType;

		$file = __DIR__ . '/../templates/Error/access.latte';
		$this->template->setFile($file);
	}
}
