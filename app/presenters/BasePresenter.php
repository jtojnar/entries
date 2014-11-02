<?php

namespace App\Presenters;

use Nette;
use App;

abstract class BasePresenter extends Nette\Application\UI\Presenter {
	const DEFAULT_LANG = 'cs';

	/** @persistent @var string */
	public $locale;

	/** @var \Kdyby\Translation\Translator @inject */
	public $translator;

	protected function startup() {
		parent::startup();

		if ($this->locale === null) {
			$detectedLocale = $this->template->locale = $this->httpRequest->detectLanguage(array('cs', 'en'));

			$this->locale = $detectedLocale ? $detectedLocale : self::DEFAULT_LANG;
			$this->canonicalize();
		}

		if (isset($this->context->parameters['siteTitle'])) {
			if (isset($this->context->parameters['siteTitle'][$this->locale])) {
				$this->template->siteTitle = $this->context->parameters['siteTitle'][$this->locale];
			} else {
				$this->template->siteTitle = $this->context->parameters['siteTitle'][self::DEFAULT_LANG];
			}
		}

		$this->template->registerHelper('categoryFormat', callback($this, 'categoryFormat'));
	}

	public function cost($persons, $chips) {
		$parameters = $this->context->parameters;
		if (isset($parameters['entries']['fees'])) {
			$fees = $parameters['entries']['fees'];
			if (isset($fees['si']) && isset($fees['person'])) {
				return $fees['si'] * intVal($chips) + $fees['person'] * intVal($persons);
			} else {
				throw new Exception('Fees incorrectly defined');
			}
		} else {
			throw new Exception('Fees not defined');
		}
	}

	public function categoryFormat($gender, $age) {
		$ages_data = $this->presenter->context->parameters['entries']['categories']['age'];
		if(count($ages_data) > 1) {
			$age = isset($ages_data[$age]) ? $ages_data[$age]['short'] : '?';
		} else {
			$age = '';
		}

		$gender_data = $this->presenter->context->parameters['entries']['categories']['gender'];
		if(count($gender_data) > 1) {
			$gender = isset($gender_data[$gender]) ? $gender_data[$gender]['short'] : '?';
		} else {
			$gender = '';
		}
		return $gender . $age;
	}
}
