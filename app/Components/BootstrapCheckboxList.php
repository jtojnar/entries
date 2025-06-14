<?php

declare(strict_types=1);

namespace App\Components;

use Nette\Forms\Controls;
use Nette\Utils\Html;

/**
 * Checkbox list input that attempts to render HTML properly for Bootstrap 5.
 * Because Bs5FormRenderer only gets us so far.
 */
final class BootstrapCheckboxList extends Controls\CheckboxList {
	/** @var bool */
	public $generateId = true;

	public function getControl(): Html {
		if ($this->hasErrors()) {
			$this->getControlPrototype()->addClass('is-invalid');
			// Mark div.form-check so that the error message is shown.
			$this->getSeparatorPrototype()->addClass('is-invalid');
		}

		/** @var Html */
		$input = Controls\MultiChoiceControl::getControl();
		$htmlId = $input->id;
		\assert(\is_string($htmlId));
		$items = $this->getItems();
		$ids = [];
		if ($this->generateId) {
			foreach ($items as $value => $label) {
				$ids[$value] = $htmlId . '-' . $value;
			}
		}

		/** @var string[] */
		$translatedItems = $this->translate($items);

		return $this->container->setHtml(
			Helpers::createInputList(
				$translatedItems,
				array_merge($input->attrs, [
					'id:' => $ids,
					'checked?' => $this->value,
					'disabled:' => $this->disabled,
					'required' => null,
					'data-nette-rules:' => [key($items) => $input->attrs['data-nette-rules']],
				]),
				['for:' => $ids] + $this->itemLabel->attrs,
				$this->separator
			)
		);
	}
}
