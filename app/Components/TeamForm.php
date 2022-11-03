<?php

declare(strict_types=1);

namespace App\Components;

use Contributte\Translation\Wrappers\NotTranslate;
use Nette\Application\UI;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Container;
use Nette\Forms\Controls;
use Nette\Utils\Json;

/**
 * Form for creating and editing teams.
 */
final class TeamForm extends UI\Form {
	public function __construct(
		/** @var array<string, string> */
		private array $countries,
		private array $parameters,
		private string $locale,
		/** @var array<string, int> */
		private array $reservationStats,
		IContainer $parent = null,
		string $name = null,
	) {
		parent::__construct($parent, $name);
	}

	public function onRender(): void {
		/** @var \Kdyby\Replicator\Container */
		$persons = $this['persons'];
		$count = iterator_count($persons->getContainers());
		$minMembers = $this->parameters['minMembers'];
		$maxMembers = $this->parameters['maxMembers'];

		if ($count >= $maxMembers) {
			/** @var Controls\SubmitButton */
			$add = $this['add'];
			$add->setDisabled();
		}

		if ($count <= $minMembers) {
			/** @var Controls\SubmitButton */
			$remove = $this['remove'];
			$remove->setDisabled();
		}
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
					$input = new BootstrapCheckbox($label);
					$container->addComponent($input, $name);
					if (isset($field['default'])) {
						$input->setDefaultValue($field['default']);
					}
				} elseif ($field['type'] === 'checkboxlist') {
					$items = [];
					foreach ($field['items'] as $itemKey => $item) {
						$items[$itemKey] = $item['label'][$locale];
					}
					$input = new BootstrapCheckboxList($label, $items);
					$container->addComponent($input, $name);
					$input->setDefaultValue($this->getDefaultFieldValue($field));
				} else {
					$input = $container->addText($name, $label)->setRequired();
				}

				if (isset($field['description'])) {
					$input->setOption('description', $field['description'][$locale]);
				}

				$isDisabled = $field['disabled'] ?? false;
				$presenter = $this->getPresenter();
				\assert($presenter !== null);
				if ($field['type'] === 'checkboxlist' || $field['type'] === 'enum') {
					\assert($input instanceof BootstrapCheckboxList || $input instanceof BootstrapRadioList);
					/** @var array<string, array{disabled: ?bool, limit: ?string}> */
					$options = $field['type'] === 'enum' ? $field['options'] : $field['items'];
					$disabledFields = array_filter(
						array_keys($options),
						function(string $optionKey) use ($options, $isDisabled): bool {
							$itemDisabled = $options[$optionKey]['disabled'] ?? $isDisabled;
							$limitName = $options[$optionKey]['limit'] ?? null;

							if ($limitName !== null) {
								$limit = $this->parameters['limits'][$limitName];
								$numberReserved = $this->reservationStats[$limitName] ?? 0;
								$itemDisabled = $isDisabled || $numberReserved >= $limit;
							}

							return $itemDisabled;
						},
					);
					if (!$presenter->getUser()->isInRole('admin')) {
						$input->setDisabled($disabledFields);
					} else {
						$input->getItemLabelPrototype()->{'data-is-visually-disabled?'} = $disabledFields;
					}
				} else {
					$limitName = $field['limit'] ?? null;
					if ($limitName !== null) {
						$limit = $this->parameters['limits'][$limitName];
						$numberReserved = $this->reservationStats[$limitName] ?? 0;
						$isDisabled = $isDisabled || $numberReserved >= $limit;
					}

					if (!$presenter->getUser()->isInRole('admin')) {
						$input->setDisabled($isDisabled);
					} else {
						$input->getControlPrototype()->setAttribute('data-is-visually-disabled', $isDisabled);
					}
				}

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

	public function addEnum(string $name, Container $container, array $field): BootstrapRadioList {
		$locale = $this->locale;

		if (isset($field['label'][$locale])) {
			$label = new NotTranslate($field['label'][$locale]);
		} else {
			$label = new NotTranslate($name . ':');
		}
		$options = array_map(
			fn(array $option): string => $option['label'][$locale],
			$field['options']
		);

		$default = $this->getDefaultFieldValue($field);

		$input = new BootstrapRadioList($label, $options);
		$input->setDefaultValue($default);
		$container->addComponent($input, $name);

		return $input;
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
