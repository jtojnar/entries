<?php

namespace App\Presenters;

use Nette;
use App\Model;

abstract class BasePresenter extends Nette\Application\UI\Presenter {
	/** @persistent @var string */
	public $locale;

	/** @var \Kdyby\Translation\Translator @inject */
	public $translator;


	protected function startup() {
		parent::startup();

		if ($this->locale === null) {
			$detectedLocale = $this->template->locale = $this->getHttpRequest()->detectLanguage(array('cs', 'en'));

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
		if (Nette\Utils\Strings::compare($age, 'open')) {
			$age = 'O';
		} else {
			$age = 'V';
		}
		
		if (Nette\Utils\Strings::compare($gender, 'male')) {
			$gender = 'M';
		} elseif (Nette\Utils\Strings::compare($gender, 'female')) {
			$gender = 'W';
		} else {
			$gender = 'X';
		}
		return $gender . $age;
	}
}
