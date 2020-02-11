<?php

declare(strict_types=1);

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

final class FormFactory {
	use Nette\SmartObject;

	/** @var ITranslator */
	private $translator;

	public function __construct(ITranslator $translator) {
		$this->translator = $translator;
	}

	public function create(string $layout = FormLayout::HORIZONTAL): Form {
		$form = new Form();

		$form->setTranslator($this->translator);
		$renderer = new Bs4FormRenderer($layout);
		$form->setRenderer($renderer);

		return $form;
	}
}
