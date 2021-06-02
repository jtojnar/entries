<?php

declare(strict_types=1);

namespace App\Presenters;

use Contributte\Translation\Translator;
use Nette;

/**
 * Base class for all presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {
	/** @var string @persistent */
	public $locale;

	/** @var Translator @inject */
	public $translator;

	/** @var Nette\DI\Container @inject */
	public $context;

	protected function startup(): void {
		parent::startup();

		if ($this->locale === null) {
			$this->locale = $this->translator->getLocale();
		}

		if (isset($this->context->parameters['siteTitle'])) {
			if (isset($this->context->parameters['siteTitle'][$this->locale])) {
				$this->template->siteTitle = $this->context->parameters['siteTitle'][$this->locale];
			} else {
				$defaultLocale = $this->translator->getDefaultLocale();

				$this->template->siteTitle = $this->context->parameters['siteTitle'][$defaultLocale];
			}
		} else {
			throw new Nette\InvalidStateException('Missing siteTitle argument');
		}
	}
}
