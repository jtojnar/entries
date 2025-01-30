<?php

declare(strict_types=1);

namespace App\Config;

use App\Model\InputModifier;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Control;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;

/**
 * @param callable(BaseControl): (BaseControl|Rules) $whenNotPlaceholder
 */
final class CustomInputModifier implements InputModifier {
	private const COUNTRY_ID = 46; // Czechia

	public static function modify(Control $input, IContainer $container, callable $whenNotPlaceholder): void {
		// we also have some inputs that are based on Nextras\FormComponents\Fragments\UIComponent\BaseControl
		if ($input instanceof BaseControl && $input->getName() === 'registry_address') {
			$pairId = 'pair-' . $input->htmlId;
			$input->setOption('id', $pairId);

			/** @var BaseControl */
			$country = $container->getComponent('country');
			$country->addCondition(Form::NOT_EQUAL, self::COUNTRY_ID)->toggle($pairId, false);
			$input->setRequired(false);
			$whenNotPlaceholder($input)->addConditionOn($country, Form::EQUAL, self::COUNTRY_ID)->setRequired();
		}
	}
}
