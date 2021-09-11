<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Helpers\Parameters;
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

	/** @var Parameters @inject */
	public $parameters;

	/** @var Nette\DI\Container @inject */
	public $context;

	protected function startup(): void {
		parent::startup();

		$detectedLocale = $this->translator->getLocale();
		if ($this->locale !== $detectedLocale) {
			$this->locale = $detectedLocale;
		}

		$this->template->siteTitle = $this->parameters->getSiteTitle($this->locale) ?? $this->parameters->getSiteTitle($this->translator->getDefaultLocale());

		if ($this->template->siteTitle === null) {
			throw new Nette\InvalidStateException('Missing siteTitle argument');
		}
	}
}
