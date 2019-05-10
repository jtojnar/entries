<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use Closure;
use Nette;
use Nette\Localization\ITranslator;

/**
 * Base class for all presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {
	/** @persistent @var string */
	public $locale;

	/** @var ITranslator @inject */
	public $translator;

	/** @var App\Model\CategoryData @inject */
	public $categories;

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

		$template->getLatte()->addFilter('categoryFormat', Closure::fromCallable([$this, 'categoryFormat']));
		$template->getLatte()->addFilter('wrapInParagraphs', Closure::fromCallable([$this, 'wrapInParagraphs']));
		$template->getLatte()->addFilter('price', function($amount) use ($translator): string {
			$currency = $this->context->parameters['entries']['fees']['currency'];
			$key = 'messages.currencies.' . $currency;
			$translated = $translator->translate($key, ['amount' => $amount]);

			return $translated === $key ? $amount : $translated;
		});
	}

	// protected since it is used by other presenters
	// TODO: move this into a separate factory
	protected function categoryFormat(App\Model\Team $team): string {
		$categoryData = $this->categories->getCategoryData();

		if (isset($categoryData[$team->category])) {
			return $categoryData[$team->category]['label'];
		}

		return $team->category;
	}

	private function wrapInParagraphs(array $arr): string {
		return implode('', array_map(function($e) {
			return '<p>' . $e . '</p>';
		}, $arr));
	}
}
