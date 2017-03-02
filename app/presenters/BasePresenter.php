<?php

namespace App\Presenters;

use Nette;
use Nette\Utils\Callback;
use App;

abstract class BasePresenter extends Nette\Application\UI\Presenter {
	/** @persistent @var string */
	public $locale;

	/** @var \Kdyby\Translation\Translator @inject */
	public $translator;

	/** @var App\Model\CategoryData @inject */
	public $categories;

	protected function startup() {
		parent::startup();

		$locales = $this->context->parameters['locales'];
		$defaultLocale = $this->context->parameters['defaultLocale'];

		if ($this->locale === null) {
			$detectedLocale = $this->template->locale = $this->context->getByType('Nette\Http\Request')->detectLanguage(array_keys($locales));

			$this->locale = $detectedLocale ? $detectedLocale : $defaultLocale;
			$this->canonicalize();
		}

		if (isset($this->context->parameters['siteTitle'])) {
			if (isset($this->context->parameters['siteTitle'][$this->locale])) {
				$this->template->siteTitle = $this->context->parameters['siteTitle'][$this->locale];
			} else {
				$this->template->siteTitle = $this->context->parameters['siteTitle'][$defaultLocale];
			}
		}

		$this->template->getLatte()->addFilter('categoryFormat', Callback::closure($this, 'categoryFormat'));
		$this->template->getLatte()->addFilter('wrapInParagraphs', Callback::closure($this, 'wrapInParagraphs'));
	}

	public function categoryFormat(App\Model\Team $team) {
		$categoryData = $this->categories->getCategoryData();

		if (isset($categoryData[$team->category])) {
			return $categoryData[$team->category]['label'];
		}

		return $team->category;
	}

	public function wrapInParagraphs(array $arr) {
		return implode('', array_map(function($e) {
			return '<p>' . $e . '</p>';
		}, $arr));
	}
}
