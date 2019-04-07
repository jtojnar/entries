<?php

declare(strict_types=1);

namespace App\Forms;

use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;
use Nextras\Forms\Rendering\Bs4FormRenderer;
use Nextras\Forms\Rendering\FormLayout;

final class FormFactory {
	use Nette\SmartObject;

	/** @var Translator */
	private $translator;

	public function __construct(Translator $translator) {
		$this->translator = $translator;
	}

	public function create($layout = FormLayout::HORIZONTAL): Form {
		$form = new Form();

		$form->setTranslator($this->translator);
		$renderer = new Bs4FormRenderer($layout);
		$form->setRenderer($renderer);

		return $form;
	}
}
