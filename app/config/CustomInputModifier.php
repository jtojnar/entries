<?php

declare(strict_types=1);

namespace App;

use Contributte\Translation\Wrappers\NotTranslate;
use Nette;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\Forms\IControl;

class CustomInputModifier {
	use Nette\SmartObject;

	public static function modify(IControl $input, IContainer $container): void {
		// we also have some inputs that are based on Nextras\FormComponents\Fragments\UIComponent\BaseControl
		if ($input instanceof BaseControl && $input->getName() === 'registry_address') {
			// Hide address field for non-Czech members since it does not apply for them.
			$pairId = 'pair-' . $input->htmlId;
			$input->setOption('id', $pairId);

			/** @var BaseControl */
			$country = $container->getComponent('country');
			$country->addCondition(Form::NOT_EQUAL, 46)->toggle($pairId, false);
			$input->setRequired(false);
			$input->addConditionOn($country, Form::EQUAL, 46)->setRequired();
		} elseif ($input instanceof BaseControl && $input->getName() === 'stage') {
			// Hide stage selection for non-newcomers, they will attend both stages.
			$pairId = 'pair-' . $input->htmlId;
			$input->setOption('id', $pairId);

			/** @var BaseControl */
			$category = $container->getComponent('category');
			$category->addCondition(Form::IS_NOT_IN, [
				'PO',
				'PZ',
			])->toggle($pairId, false);
			$input->setRequired(false);
			$input->addConditionOn($category, Form::IS_IN, [
				'PO',
				'PZ',
			])->setRequired();
		} elseif ($input instanceof BaseControl && $input->getName() === 'accommodation') {
			// Disables listed option of accommodation.
			$disabledOptions = [
				'Potkavarna',
				'Karlovka',
			];
			$input->form->onRender[] =function($form) use ($input, $disabledOptions) {
				// Needs to be disabled just before the form rendering, since we only supply
				// values for editing long after the input is created (and CustomInputModifier run).
				$input->setDisabled(array_diff($disabledOptions, [$input->getValue()]));
			};
		} elseif ($input instanceof BaseControl && $input->getName() === 'boarding') {
			// Do not allow ordering half board when not using accommodation.
			/** @var BaseControl */
			$accommodation = $container->getComponent('accommodation');
			$withoutAccomodation = $input->addConditionOn($accommodation, Form::EQUAL, 'none');
			// Form::IS_IN considers empty values invalid instead of trivially valid.
			$nonEmptyBoardingWithoutAccomodation = $withoutAccomodation->addCondition(Form::FILLED);
			// Cannot use Form::IS_NOT_IN directly since that is just a negation IS_IN, not a complement.
			$nonEmptyBoardingWithoutAccomodation->addRule(
				Form::IS_IN,
				new NotTranslate(
					$input->getTranslator()->getLocale() === 'cs'
					? 'Polopenze je nabízena pouze pro ubytované na některě z chalup.'
					: 'Half board is only available if you stay at one of the cottages.'
				),
				[
					'sat_meal',
					'sun_meal',
				]
			);
		}
	}
}
