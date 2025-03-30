<?php

declare(strict_types=1);

namespace App\Config;

use App\Locale\Translated;
use App\Model\InputModifier;
use Exception;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Control;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\Forms\Rules;

/**
 * Makes the person’s `registry_bcn` field required if and only if their `country` field is Czechia.
 */
final class BcnMandatoryForCzechs implements InputModifier {
	private const COUNTRY_ID = 46; // Czechia

	/**
	 * @param callable(BaseControl): (BaseControl|Rules) $whenNotPlaceholder rules attached to the returned value will only be checked if the control passed as the argument to the `callable` does not belong to a placeholder person
	 */
	public static function modify(Control $input, IContainer $container, callable $whenNotPlaceholder): void {
		// we also have some inputs that are based on Nextras\FormComponents\Fragments\UIComponent\BaseControl
		if ($input instanceof BaseControl && $input->getName() === 'registry_bcn') {
			$pairId = 'pair-' . $input->htmlId;
			$input->setOption('id', $pairId);

			/** @var BaseControl */
			$country = $container->getComponent('country');
			$country->addCondition(Form::NOT_EQUAL, self::COUNTRY_ID)->toggle($pairId, false);
			$input->setRequired(false);
			$whenNotPlaceholder($input)->addConditionOn($country, Form::EQUAL, self::COUNTRY_ID)->setRequired();
		} elseif ($input instanceof BaseControl && $input->getName() === 'accommodation') {
			$tent = $container->getForm()->getComponent('tent');
			$input->addConditionOn($tent, Form::Equal, true)->addRule(Form::Equal, new class implements Translated {
				public function getMessage(string $lang): string {
					return match ($lang) {
						'en' => 'You cannot select both team tent accommodation and individual accommodation',
						'cs' => 'Týmový stan vylučuje individuální ubytování členů týmu',
						default => throw new Exception("Unsupported language $lang"),
					};
				}
			}, 'none');
		}
	}
}
