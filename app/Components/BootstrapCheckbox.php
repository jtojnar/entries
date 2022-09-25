<?php

declare(strict_types=1);

namespace App\Components;

use Nette\Forms\Controls;
use Nette\Utils\Html;

/**
 * Checkbox input that renders HTML properly for Bootstrap 5.
 * Because Bs5FormRenderer only gets us so far.
 */
final class BootstrapCheckbox extends Controls\Checkbox {
	public function getControl(): Html {
		if ($this->hasErrors()) {
			$this->getControlPrototype()->addClass('is-invalid');
		}

		// We want the checkbox to be placed outside the label.
		$container = $this->getContainerPrototype();
		$container->insert(null, $this->getControlPart());
		$container->insert(null, $this->getLabelPart());

		return $container;
	}
}
