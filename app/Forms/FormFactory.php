<?php

declare(strict_types=1);

namespace App\Forms;

use Nette\Application\UI\Form;
use Nette\Localization\Translator;
use Nextras\FormsRendering\Renderers\Bs5FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

final class FormFactory {
	public function __construct(
		private readonly Translator $translator,
	) {
	}

	/**
	 * @param FormLayout::* $layout
	 */
	public function create(string $layout = FormLayout::HORIZONTAL): Form {
		$form = new Form();

		$form->setTranslator($this->translator);
		$renderer = new Bs5FormRenderer($layout);
		$form->setRenderer($renderer);

		return $form;
	}
}
