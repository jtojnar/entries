<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Helpers\Parameters;
use Contributte\Translation\Translator;
use Nette;
use Nette\Application\Attributes\Persistent;
use Nette\DI\Attributes\Inject;
use Override;

/**
 * Base class for all presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {
	#[Persistent]
	public string $locale = '';

	#[Inject]
	public Translator $translator;

	#[Inject]
	public Parameters $parameters;

	#[Inject]
	public Nette\DI\Container $context;

	#[Override]
	protected function startup(): void {
		parent::startup();

		$detectedLocale = $this->translator->getLocale();
		if ($this->locale !== $detectedLocale) {
			$this->locale = $detectedLocale;
		}

		$this->template->siteTitle = $this->parameters->getSiteTitle($this->locale) ?? $this->parameters->getSiteTitle($this->translator->getDefaultLocale());
		$this->template->locale = $this->locale;

		if ($this->template->siteTitle === null) {
			throw new Nette\InvalidStateException('Missing siteTitle argument');
		}
	}
}
