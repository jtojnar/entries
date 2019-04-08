<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use Nette;
use Nette\Utils\Callback;

abstract class BasePresenter extends Nette\Application\UI\Presenter {
	/** @persistent @var string */
	public $locale;

	/** @var \Kdyby\Translation\Translator @inject */
	public $translator;

	/** @var App\Model\CategoryData @inject */
	public $categories;

	protected function startup(): void {
		parent::startup();

		$locales = $this->context->parameters['locales'];
		$defaultLocale = $this->context->parameters['defaultLocale'];

		if ($this->locale === null) {
			$detectedLocale = $this->template->locale = $this->context->getByType('Nette\Http\Request')->detectLanguage(array_keys($locales));

			$this->locale = $detectedLocale ? $detectedLocale : $defaultLocale;
			$this->canonicalize();
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

		$template->getLatte()->addFilter('categoryFormat', Callback::closure($this, 'categoryFormat'));
		$template->getLatte()->addFilter('wrapInParagraphs', Callback::closure($this, 'wrapInParagraphs'));
		$template->getLatte()->addFilter('price', function($amount) {
			$currency = $this->context->parameters['entries']['fees']['currency'];
			$key = 'messages.currencies.' . $currency;
			$translated = $this->translator->translate($key, ['amount' => $amount]);

			return $translated === $key ? $amount : $translated;
		});
	}

	public function categoryFormat(App\Model\Team $team): string {
		$categoryData = $this->categories->getCategoryData();

		if (isset($categoryData[$team->category])) {
			return $categoryData[$team->category]['label'];
		}

		return $team->category;
	}

	public function wrapInParagraphs(array $arr): string {
		return implode('', array_map(function($e) {
			return '<p>' . $e . '</p>';
		}, $arr));
	}
}
