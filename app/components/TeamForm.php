<?php

declare(strict_types=1);

namespace App\Components;

use Closure;
use Nette\Application\UI;
use Nette\ComponentModel\IContainer;
use Nette\Utils\Html;
use Nette\Utils\Json;

class TeamForm extends UI\Form {
	/** @var array */
	private $countries;

	/** @var array */
	private $parameters;

	/** @var string */
	private $locale;

	public function __construct(array $countries, array $parameters, string $locale, IContainer $parent = null, string $name = null) {
		parent::__construct($parent, $name);
		$this->countries = $countries;
		$this->parameters = $parameters;
		$this->locale = $locale;
	}

	public function onRender(): void {
		$count = iterator_count($this['persons']->getContainers());
		$minMembers = $this->parameters['minMembers'];
		$maxMembers = $this->parameters['maxMembers'];

		if ($count >= $maxMembers) {
			$this['add']->setDisabled();
		}

		if ($count <= $minMembers) {
			$this['remove']->setDisabled();
		}
	}

	public function addCustomFields($fields, $container): void {
		$locale = $this->locale;

		foreach ($fields as $name => $field) {
			if (isset($field['type'])) {
				if (isset($field['label'][$locale])) {
					$label = Html::el()->setText($field['label'][$locale]);
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
					$input = $container->addText($name, $label)->setType('tel')->setRequired();
				} elseif ($field['type'] === 'enum') {
					$input = $this->addEnum($name, $container, $field)->setRequired();
				} elseif ($field['type'] === 'checkbox') {
					$input = $container->addCheckBox($name, $label);
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

				if (isset($this->parameters['customInputModifier'])) {
					$customInputModifier = Closure::fromCallable([$this->parameters['customInputModifier'], 'modify']);
					$customInputModifier($input, $container);
				}
			}
		}
	}

	public function addSportident($name, $container): SportidentControl {
		$recommendedCardCapacity = $this->parameters['recommendedCardCapacity'];

		$si = new SportidentControl('messages.team.person.si.label', $recommendedCardCapacity);
		$container->addComponent($si, $name);

		return $si;
	}

	public function addEnum($name, $container, $field) {
		$locale = $this->locale;

		if (isset($field['label'][$locale])) {
			$label = Html::el()->setText($field['label'][$locale]);
		} else {
			$label = $name . ':';
		}
		$options = array_map(function($option) use ($locale) {
			return $option['label'][$locale];
		}, $field['options']);

		$default = $this->getDefaultFieldValue($field);

		return $container->addRadioList($name, $label, $options)->setDefaultValue($default);
	}

	public function isFieldDisabled($field) {
		return $field['disabled'] ?? false;
	}

	public function getDefaultFieldValue($field) {
		if ($field['type'] === 'enum') {
			$full_options = $field['options'];

			return array_reduce(array_keys($field['options']), function($carry, $key) use ($full_options) {
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

			return null;
		}
	}
}
