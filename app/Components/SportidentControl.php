<?php

declare(strict_types=1);

namespace App\Components;

use Nette;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Forms\Helpers;
use Nette\Forms\Rules;
use Nextras\FormComponents\Fragments\UIComponent\BaseControl as NextrasBaseControl;
use stdClass;

final class SportidentControl extends NextrasBaseControl {
	public const string NAME_CARD_ID = 'cardId';

	public const string NAME_NEEDED = 'needed';

	private const string SI_PATTERN = '[0-9]+';

	/** @var TextInput cardIdControl entry for card id */
	protected TextInput $cardIdControl;

	/** @var Checkbox neededControl checkbox for requesting a loan */
	protected Checkbox $neededControl;

	/**
	 * @param callable(BaseControl): (BaseControl|Rules) $whenNotPlaceholder
	 */
	public function __construct(string $label, int $recommendedCardCapacity, callable $whenNotPlaceholder) {
		$this->cardIdControl = new TextInput();
		$this->neededControl = new Checkbox('messages.team.person.si.rent');

		$this->addComponent($this->cardIdControl, self::NAME_CARD_ID);
		$this->addComponent($this->neededControl, self::NAME_NEEDED);

		$this->cardIdControl->getControlPrototype()->pattern = self::SI_PATTERN;

		// Asking for cardIdControl->htmlId before the control is attached
		// to a form freezes it at `frm-cardId`
		$this->monitor(Form::class, function(Form $form) use ($recommendedCardCapacity, $whenNotPlaceholder): void {
			\assert(\is_string($this->cardIdControl->htmlId)); // For PHPStan.
			$this->cardIdControl->addConditionOn($this->neededControl, Form::EQUAL, false)->addRule(Form::INTEGER);
			$whenNotPlaceholder($this->cardIdControl)->addConditionOn($this->neededControl, Form::EQUAL, false)->addRule(Form::FILLED);
			$this->neededControl->addCondition(Form::EQUAL, true)->toggle($this->cardIdControl->htmlId, false);

			// We want to warn user when they register with a SI card with
			// a potentially insufficient capacity for the race.
			// https://www.sportident.co.uk/equipment/information_sheets/SPORTident-CardComparison.PDF
			if ($recommendedCardCapacity > 30) {
				$rules = new Rules($this->neededControl);
				$rules = $rules->addConditionOn($this->neededControl, Form::EQUAL, false);
				/** @var \Contributte\Translation\Translator */
				$translator = $this->getTranslator();

				@$rules->addRule(~Form::RANGE, $translator->translate('messages.team.person.si.warning.low-capacity-si5'), [1, 499999]); // @ - negative rules are deprecated
				@$rules->addRule(~Form::RANGE, $translator->translate('messages.team.person.si.warning.low-capacity-si8'), [2_000_001, 2_999_999]); // @ - negative rules are deprecated

				if ($recommendedCardCapacity > 50) {
					@$rules->addRule(~Form::RANGE, $translator->translate('messages.team.person.si.warning.low-capacity-si9'), [1_000_000, 1_999_999]); // @ - negative rules are deprecated
				}

				if ($recommendedCardCapacity > 64) {
					@$rules->addRule(~Form::RANGE, $translator->translate('messages.team.person.si.warning.low-capacity-si6'), [500001, 999999]); // @ - negative rules are deprecated
				}

				$this->neededControl->addCondition(Form::EQUAL, true)->toggle($this->cardIdControl->htmlId . '-warning', false);

				$this->cardIdControl->getControlPrototype()->setAttribute('data-form-warning-rules', Helpers::exportRules($rules));
			}
		});

		parent::__construct($label);
	}

	/**
	 * @param array|stdClass|null $value
	 */
	public function setValue($value): self {
		if ($value === null) {
			$this->cardIdControl->setValue('');
			$this->neededControl->setValue(false);

			return $this;
		}

		if (!\is_array($value) && !$value instanceof stdClass) {
			throw new Nette\InvalidArgumentException('Sportident takes an array, stdClass or null');
		}

		// cast the stdClass
		$value = (array) $value;

		if (!isset($value[self::NAME_NEEDED]) && !isset($value[self::NAME_CARD_ID])) {
			throw new Nette\InvalidArgumentException('Sportident takes an array containing either rented, or a numeric ID');
		}

		if (isset($value[self::NAME_NEEDED]) && isset($value[self::NAME_CARD_ID])) {
			throw new Nette\InvalidArgumentException('Sportident takes an array containing either rented, or a numeric ID');
		}

		$this->cardIdControl->setValue($value[self::NAME_CARD_ID] ?? '');
		$this->neededControl->setValue($value[self::NAME_NEEDED] ?? false);

		return $this;
	}

	public function getValue(): array {
		return $this->neededControl->getValue() ? [self::NAME_NEEDED => true] : [self::NAME_CARD_ID => $this->cardIdControl->getValue()];
	}

	public function loadHttpData(): void {
		$this->cardIdControl->loadHttpData();
		$this->neededControl->loadHttpData();
	}

	public function isFilled(): bool {
		return !empty($this->cardIdControl->getValue()) || $this->neededControl->getValue() === true;
	}

	public function getControl(): string {
		$this->setOption('rendered', true);

		return $this->getControlPart(static::NAME_CARD_ID) . $this->getControlPart(static::NAME_NEEDED);
	}

	public function getControlPart(?string $key = null): Nette\Utils\Html {
		if ($key === static::NAME_CARD_ID) {
			return $this->cardIdControl->getControl();
		} elseif ($key === static::NAME_NEEDED) {
			return $this->neededControl->getControl();
		}

		throw new Nette\InvalidArgumentException('Part ' . ($key ?: 'null') . ' does not exist');
	}

	public function validate(?array $controls = null): void {
		$this->cardIdControl->validate();
		$this->neededControl->validate();
	}
}
