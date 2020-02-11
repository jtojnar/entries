<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Localization\ITranslator;

/**
 * Base class for all presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {
	/** @var string @persistent */
	public $locale;

	/** @var ITranslator @inject */
	public $translator;

	protected function startup(): void {
		parent::startup();

		/** @var \Contributte\Translation\Translator */
		$translator = $this->translator;

		$defaultLocale = $translator->getDefaultLocale();

		if ($this->locale === null) {
			$this->locale = $translator->getLocale();
		}

		/** @var Nette\Bridges\ApplicationLatte\Template $template */
		$template = $this->template;

		if (isset($this->context->parameters['siteTitle'])) {
			if (isset($this->context->parameters['siteTitle'][$this->locale])) {
				$template->siteTitle = $this->context->parameters['siteTitle'][$this->locale];
			} else {
				$template->siteTitle = $this->context->parameters['siteTitle'][$defaultLocale];
			}
		}
	}
}
