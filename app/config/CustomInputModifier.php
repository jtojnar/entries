<?php

declare(strict_types=1);

namespace App;

use Nette;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;

class CustomInputModifier {
	use Nette\SmartObject;

	public static function modify(BaseControl $input, IContainer $container): void {
		if ($input->getName() === 'registry_address') {
			$pairId = 'pair-' . $input->htmlId;
			$input->setOption('id', $pairId);

			/** @var BaseControl */
			$country = $container->getComponent('country');
			$country->addCondition(Form::NOT_EQUAL, 46)->toggle($pairId, false);
		}
	}
}
