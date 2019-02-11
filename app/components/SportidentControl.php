<?php

declare(strict_types=1);

namespace App\Components;

use App;
use Nette;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nextras\Forms\Controls\Fragments\ComponentControlTrait;

class SportidentControl extends BaseControl implements IContainer {
	use ComponentControlTrait;

	/** @var string */
	public const NAME_CARD_ID = 'cardId';

	/** @var string */
	public const NAME_NEEDED = 'needed';

	/** @var string */
	private const SI_PATTERN = '[0-9]+';

	/** @var TextInput cardIdControl entry for card id */
	protected $cardIdControl;

	/** @var Checkbox neededControl checkbox for requesting a loan */
	protected $neededControl;

	public function __construct($label) {
		$this->cardIdControl = new TextInput();
		$this->neededControl = new Checkbox('messages.team.person.si.rent');

		parent::__construct($label);

		$this->addComponent($this->cardIdControl, self::NAME_CARD_ID);
		$this->addComponent($this->neededControl, self::NAME_NEEDED);

		$this->cardIdControl->getControlPrototype()->pattern = self::SI_PATTERN;

		// Asking for cardIdControl->htmlId before the control is attached
		// to a form freezes it at `frm-cardId`
		$this->monitor(Form::class, function(Form $form): void {
			$this->cardIdControl->addConditionOn($this->neededControl, Form::EQUAL, false)->addRule(Form::FILLED)->addRule(Form::INTEGER);
			$this->neededControl->addCondition(Form::EQUAL, true)->toggle($this->cardIdControl->htmlId, false);
		});
	}

	public function setValue($value): self {
		if ($value === null) {
			$this->cardIdControl->setValue('');
			$this->neededControl->setValue(false);

			return $this;
		}

		if (!\is_array($value) && !$value instanceof \stdClass) {
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

		throw new Nette\InvalidArgumentException('Part ' . $key . ' does not exist');
	}
}
