<?php

namespace App\Presenters;

use Nette;
use App;

abstract class BasePresenter extends Nette\Application\UI\Presenter {
	/** @persistent @var string */
	public $locale;

	/** @var \Kdyby\Translation\Translator @inject */
	public $translator;

	protected function startup() {
		parent::startup();

		$locales = $this->context->parameters['locales'];
		$defaultLocale = $this->context->parameters['defaultLocale'];

		if ($this->locale === null) {
			$detectedLocale = $this->template->locale = $this->context->httpRequest->detectLanguage(array_keys($locales));

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

		$this->template->registerHelper('categoryFormat', callback($this, 'categoryFormat'));
		$this->template->registerHelper('wrapInParagraphs', callback($this, 'wrapInParagraphs'));
	}

	public function categoryFormat(App\Model\Team $team) {
		if (isset($this->presenter->context->parameters['entries']['categories']['custom'])) {
			return callback($this->presenter->context->parameters['entries']['categories']['custom'], 'detectCategory')->invoke($team, $this);
		}

		$gender = $team->genderclass;
		$age = $team->ageclass;
		$ages_data = $this->presenter->context->parameters['entries']['categories']['age'];
		if (count($ages_data) > 1) {
			$age = isset($ages_data[$age]) ? $ages_data[$age]['short'] : '?';
		} else {
			$age = '';
		}

		$gender_data = $this->presenter->context->parameters['entries']['categories']['gender'];
		if (count($gender_data) > 1) {
			$gender = isset($gender_data[$gender]) ? $gender_data[$gender]['short'] : '?';
		} else {
			$gender = '';
		}
		return $gender . $age;
	}

	public function wrapInParagraphs(array $arr) {
		return implode('', array_map(function($e) {
			return '<p>' . $e . '</p>';
		}, $arr));
	}
}
