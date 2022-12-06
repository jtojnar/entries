<?php

declare(strict_types=1);

namespace App\Config;

use App\Model\InputModifier;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Control;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;

final class CustomInputModifier implements InputModifier {
	public static function modify(Control $input, IContainer $container): void {
		// we also have some inputs that are based on Nextras\FormComponents\Fragments\UIComponent\BaseControl
		if ($input instanceof BaseControl && $input->getName() === 'registry_address') {
			$pairId = 'pair-' . $input->htmlId;
			$input->setOption('id', $pairId);

			/** @var BaseControl */
			$country = $container->getComponent('country');
			$country->addCondition(Form::NOT_EQUAL, 46)->toggle($pairId, false);
			$input->setRequired(false);
			$input->addConditionOn($country, Form::EQUAL, 46)->setRequired();
		}
	}
}
