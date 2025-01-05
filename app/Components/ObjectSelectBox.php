<?php

// SPDX-License-Identifier: BSD-3-Clause
// SPDX-FileCopyrightText: 2004 David Grudl
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Components;

use App\Locale\Translated;
use Contributte\Translation\Wrappers\NotTranslate;
use InvalidArgumentException;
use Nette;
use Nette\Forms\Controls;
use Nette\Utils\Html;
use Override;

/**
 * Select box control that allows single item selection.
 * This is based on `Controls\SelectBox` but support non-string optgroup labels.
 */
class ObjectSelectBox extends Controls\ChoiceControl {
	/** validation rule */
	public const VALID = ':selectBoxValid';

	/** @var array<string,string|Translated|NotTranslate>|array<OptGroup> */
	private array $options = [];

	private string|Translated|NotTranslate|false $prompt = false;

	/** @var array<string, mixed> */
	private array $optionAttributes = [];

	/**
	 * @param string|Translated $label
	 * @param array<string,string|Translated|NotTranslate>|array<OptGroup> $items
	 */
	public function __construct($label = null, ?array $items = null) {
		parent::__construct($label, $items);
		$this->setOption('type', 'select');
		$this
			->addCondition(fn(): bool => $this->prompt === false && $this->options && $this->control->size < 2)
			->addRule(Nette\Forms\Form::FILLED, Nette\Forms\Validator::$messages[self::VALID]);
	}

	/**
	 * Sets first prompt item in select box.
	 */
	public function setPrompt(string|Translated|NotTranslate|false $prompt): static {
		$this->prompt = $prompt;

		return $this;
	}

	/**
	 * Returns first prompt item?
	 */
	public function getPrompt(): string|Translated|NotTranslate|false {
		return $this->prompt;
	}

	/**
	 * Sets options and option groups from which to choose.
	 *
	 * @param array<string,string|Translated|NotTranslate>|array<OptGroup> $items
	 */
	#[Override]
	public function setItems(array $items, ?bool $useKeys = null): static {
		if ($useKeys !== null) {
			throw new InvalidArgumentException('useKeys argument is not supported.');
		}

		$this->options = $items;

		$isNested = false;
		foreach ($items as $item) {
			$isNested = $item instanceof OptGroup;
			break;
		}
		if ($isNested) {
			/** @var OptGroup[] */
			$nestedItems = $items;
			$allItems = array_map(static fn(OptGroup $group): array => $group->options, $nestedItems);
			$items = Nette\Utils\Arrays::flatten($allItems, preserveKeys: true);
		}

		return parent::setItems($items);
	}

	#[Override]
	public function getControl(): Html {
		$items = $this->prompt === false ? [] : ['' => $this->translate($this->prompt)];
		foreach ($this->options as $key => $value) {
			if ($value instanceof OptGroup) {
				$items[$this->translate($value->label)] = $this->translate($value->options);
			} else {
				$items[$key] = $this->translate($value);
			}
		}

		$control = parent::getControl();
		\assert($control instanceof Html);

		$select = Nette\Forms\Helpers::createSelectBox(
			$items,
			[
				'disabled:' => \is_array($this->disabled) ? $this->disabled : null,
			] + $this->optionAttributes,
			$this->value
		);
		$select->addAttributes($control->attrs);
		// Bs5FormRenderer would do it automatically for SelectBox
		$select->addClass('form-select');

		return $select;
	}

	/**
	 * @param array<string, mixed> $attributes
	 */
	public function addOptionAttributes(array $attributes): static {
		$this->optionAttributes = $attributes + $this->optionAttributes;

		return $this;
	}

	public function setOptionAttribute(string $name, mixed $value = true): static {
		$this->optionAttributes[$name] = $value;

		return $this;
	}

	public function isOk(): bool {
		return $this->isDisabled()
			|| $this->prompt !== false
			|| $this->getValue() !== null
			|| !$this->options
			|| $this->control->size > 1;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getOptionAttributes(): array {
		return $this->optionAttributes;
	}
}
