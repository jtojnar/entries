<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

/**
 * @property Nette\Application\UI\Template $template
 */
final class Error4xxPresenter extends BasePresenter {
	public function startup(): void {
		parent::startup();
		if ($this->getRequest() === null || !$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
			$this->error();
		}
	}

	public function renderDefault(Nette\Application\BadRequestException $exception): void {
		// load template 403.latte or 404.latte or ... 4xx.latte
		$file = __DIR__ . "/../Templates/Error/{$exception->getCode()}.latte";
		$file = is_file($file) ? $file : __DIR__ . '/../Templates/Error/4xx.latte';
		$this->template->setFile($file);
	}
}
