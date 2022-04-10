<?php

declare(strict_types=1);

namespace App\Components;

use Contributte\Translation\Wrappers\NotTranslate;
use Nette\Application\UI;
use Nette\Forms\Container;
use Nette\Forms\Controls;
use Nette\Utils\Json;

/**
 * Form for creating and editing teams.
 */
class TeamForm extends UI\Form {
	/** @var array<string, string> */
	private $countries;

	/** @var array */
	private $parameters;

	/** @var string */
	private $locale;

	public function __construct(array $countries, array $parameters, string $locale) {
		parent::__construct();
		$this->countries = $countries;
		$this->parameters = $parameters;
		$this->locale = $locale;
	}

	public function addCustomFields(array $fields, Container $container): void {
		$locale = $this->locale;

		foreach ($fields as $name => $field) {
			if (isset($field['type'])) {
				if (isset($field['label'][$locale])) {
					$label = new NotTranslate($field['label'][$locale]);
				} elseif ($field['type'] === 'country') {
					$label = 'messages.team.person.country.label';
				} elseif ($field['type'] === 'phone') {
					$label = 'messages.team.phone.label';
				} elseif ($field['type'] === 'sportident') {
					$label = 'messages.team.person.si.label';
				} else {
					$label = $name . ':';
				}

				if ($field['type'] === 'sportident') {
					$input = $this->addSportident($name, $container);
				} elseif ($field['type'] === 'country') {
					$input = $container->addSelect($name, $label, $this->countries)->setPrompt('messages.team.person.country.default')->setRequired();
					if (isset($field['default'])) {
						$input->setDefaultValue($field['default']);
					}
				} elseif ($field['type'] === 'phone') {
					$input = $container->addText($name, $label)->setHtmlType('tel')->setRequired();
				} elseif ($field['type'] === 'enum') {
					$input = $this->addEnum($name, $container, $field)->setRequired();
				} elseif ($field['type'] === 'checkbox') {
					$input = $container->addCheckbox($name, $label);
					if (isset($field['default'])) {
						$input->setDefaultValue($field['default']);
					}
				} elseif ($field['type'] === 'checkboxlist') {
					$items = [];
					foreach ($field['items'] as $itemKey => $item) {
						$items[$itemKey] = $item['label'][$locale];
					}

					$input = $container->addCheckBoxList($name, $label, $items);
					$input->setDefaultValue($this->getDefaultFieldValue($field));
				} else {
					$input = $container->addText($name, $label)->setRequired();
				}

				if (isset($field['description'])) {
					$input->setOption('description', $field['description'][$locale]);
				}

				$input->setDisabled($field['disabled'] ?? false);

				if (isset($field['applicableCategories'])) {
					$input->getControlPrototype()->{'data-applicable-categories'} = Json::encode($field['applicableCategories']);
				}

				/** @var ?callable */
				$customInputModifier = $this->parameters['customInputModifier'] ?? null;
				if ($customInputModifier !== null) {
					$customInputModifier($input, $container);
				}
			}
		}
	}

	public function addSportident(string $name, Container $container): SportidentControl {
		$recommendedCardCapacity = $this->parameters['recommendedCardCapacity'];

		$si = new SportidentControl('messages.team.person.si.label', $recommendedCardCapacity);
		$container->addComponent($si, $name);

		return $si;
	}

	public function addEnum(string $name, Container $container, array $field): Controls\RadioList {
		$locale = $this->locale;

		if (isset($field['label'][$locale])) {
			$label = new NotTranslate($field['label'][$locale]);
		} else {
			$label = new NotTranslate($name . ':');
		}
		$options = array_map(function(array $option) use ($locale): string {
			return $option['label'][$locale];
		}, $field['options']);

		$default = $this->getDefaultFieldValue($field);

		return $container->addRadioList($name, $label, $options)->setDefaultValue($default);
	}

	public function isFieldDisabled(array $field): bool {
		return $field['disabled'] ?? false;
	}

	/**
	 * @return ?mixed
	 */
	public function getDefaultFieldValue(array $field) {
		if ($field['type'] === 'enum') {
			/** @var array<string, array> */ // For PHPStan.
			$full_options = $field['options'];

			return array_reduce(array_keys($full_options), function(?string $carry, string $key) use ($full_options): ?string {
				$item = $full_options[$key];
				if (isset($item['default']) && $item['default'] === true) {
					return $key;
				}

				return $carry;
			});
		} elseif ($field['type'] === 'checkboxlist') {
			$default = [];

			foreach ($field['items'] as $itemKey => $item) {
				if (isset($item['default']) && $item['default']) {
					$default[] = $itemKey;
				}
			}

			return $default;
		} else {
			return $field['default'] ?? null;
		}
	}
}
