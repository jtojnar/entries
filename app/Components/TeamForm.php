<?php

declare(strict_types=1);

namespace App\Components;

use App\Locale\Translated;
use App\Model\Configuration\Entries;
use App\Model\Configuration\Fields;
use App\Model\Configuration\Fields\Field;
use App\Model\InputModifier;
use Contributte\Translation\Wrappers\NotTranslate;
use Nette\Application\UI;
use Nette\Forms\Container;
use Nette\Forms\Controls;
use Nette\Utils\Json;

/**
 * Form for creating and editing teams.
 */
final class TeamForm extends UI\Form {
	public function __construct(
		/** @var string[] */
		private readonly array $countries,
		/** @var array<string, int> */
		private readonly array $reservationStats,
		private readonly Entries $entries,
		private readonly bool $canModifyLocked,
	) {
		parent::__construct();

		$this->onRender[] = $this->updatePersonButtonsState(...);
		$this->onValidate[] = $this->checkCategoryConstraints(...);
	}

	private function updatePersonButtonsState(): void {
		/** @var \Kdyby\Replicator\Container */
		$persons = $this['persons'];
		$count = iterator_count($persons->getContainers());
		$minMembers = $this->entries->minMembers;
		$maxMembers = $this->entries->maxMembers;

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
		foreach ($fields as $field) {
			$name = $field->name;
			$label = $field->label;

			if ($field instanceof Fields\SportidentField) {
				$input = $this->addSportident($name, $container);
			} elseif ($field instanceof Fields\CountryField) {
				$input = $container->addSelect($name, $label, $this->countries)->setPrompt('messages.team.person.country.default')->setRequired();
				if ($field->default !== null) {
					$input->setDefaultValue($field->default);
				}
			} elseif ($field instanceof Fields\PhoneField) {
				$input = $container->addText($name, $label)->setHtmlType('tel')->setRequired();
			} elseif ($field instanceof Fields\EnumField) {
				$input = $this->addEnum($name, $container, $field)->setRequired();
			} elseif ($field instanceof Fields\CheckboxField) {
				$input = new BootstrapCheckbox($label);
				$container->addComponent($input, $name);
				if ($field->default !== null) {
					$input->setDefaultValue($field->default);
				}
			} elseif ($field instanceof Fields\CheckboxlistField) {
				$items = [];
				foreach ($field->items as $item) {
					$items[$item->name] = $item->label;
				}
				$input = new BootstrapCheckboxList($label, $items);
				$container->addComponent($input, $name);
				$input->setDefaultValue($this->getDefaultFieldValue($field));
			} else {
				$input = $container->addText($name, $label)->setRequired();
			}

			if ($field->description !== null) {
				$input->setOption('description', $field->description);
			}

			$isDisabled = $field->disabled ?? false;
			if ($field instanceof Fields\CheckboxlistField || $field instanceof Fields\EnumField) {
				\assert($input instanceof BootstrapCheckboxList || $input instanceof BootstrapRadioList);
				$options = $field instanceof Fields\EnumField ? $field->options : $field->items;
				$disabledFields = array_filter(
					$options,
					function(Fields\Item $item) use ($isDisabled): bool {
						$itemDisabled = $item->disabled ?? $isDisabled;
						$limitName = $item->limitName ?? null;

						if ($limitName !== null) {
							$limit = $this->entries->limits[$limitName];
							$numberReserved = $this->reservationStats[$limitName] ?? 0;
							$itemDisabled = $isDisabled || $numberReserved >= $limit;
						}

						return $itemDisabled;
					},
				);
				if (!$this->canModifyLocked) {
					$input->setDisabled($disabledFields);
				} else {
					$input->getItemLabelPrototype()->setAttribute('data-is-visually-disabled?', $disabledFields);
				}
			} else {
				$limitName = $field instanceof Fields\LimitableField ? $field->getLimitName() : null;
				if ($limitName !== null) {
					$limit = $this->entries->limits[$limitName];
					$numberReserved = $this->reservationStats[$limitName] ?? 0;
					$isDisabled = $isDisabled || $numberReserved >= $limit;
				}

				if (!$this->canModifyLocked) {
					$input->setDisabled($isDisabled);
				} else {
					$input->getControlPrototype()->setAttribute('data-is-visually-disabled', $isDisabled);
				}
			}

			if (isset($field->applicableCategories)) {
				$input->getControlPrototype()->setAttribute('data-applicable-categories', Json::encode($field->applicableCategories));
			}

			/** @var ?class-string<InputModifier> */
			$inputModifier = $this->entries->inputModifier;
			if ($inputModifier !== null) {
				$inputModifier::modify($input, $container);
			}
		}
	}

	public function addSportident(string $name, Container $container): SportidentControl {
		$recommendedCardCapacity = $this->entries->recommendedCardCapacity;

		$si = new SportidentControl('messages.team.person.si.label', $recommendedCardCapacity);
		$container->addComponent($si, $name);

		return $si;
	}

	public function addEnum(string $name, Container $container, Fields\EnumField $field): BootstrapRadioList {
		$options = array_combine(
			array_map(
				fn(Fields\Item $option): string => $option->name,
				$field->options,
			),
			array_map(
				fn(Fields\Item $option): Translated|NotTranslate|string => $option->label,
				$field->options,
			),
		);

		$default = $this->getDefaultFieldValue($field);

		$input = new BootstrapRadioList($field->label, $options);
		$input->setDefaultValue($default);
		$container->addComponent($input, $name);

		return $input;
	}

	public function isFieldDisabled(Field $field): bool {
		return $field->disabled ?? false;
	}

	/**
	 * @return ?mixed
	 */
	public function getDefaultFieldValue(Field $field) {
		if ($field instanceof Fields\EnumField) {
			return array_reduce(
				$field->options,
				static function(?string $carry, Fields\Item $item): ?string {
					if ($item->default === true) {
						return $item->name;
					}

					return $carry;
				},
			);
		} elseif ($field instanceof Fields\CheckboxlistField) {
			$default = [];

			foreach ($field->items as $item) {
				if ($item->default === true) {
					$default[] = $item->name;
				}
			}

			return $default;
		} else {
			return $field->default ?? null;
		}
	}

	private function checkCategoryConstraints(self $form, \stdClass $data): void {
		// If submitter is `true`, no specific submit button was pressed but let’s check the form.
		// Since `onValidate` is only called on form submission, it cannot be `false` but we will use `is_bool` to satisfy PHPStan.
		$validationScope = \is_bool($form->isSubmitted()) ? null : $form->isSubmitted()->getValidationScope();
		if ($validationScope === []) {
			// onValidate event is called even when submit button’s validation scope is empty.
			// But we do not want to validate when adding/removing team members, which sets the scope to empty array.
			return;
		}

		$categoryField = $form->getComponent('category');
		$constraints = $this->entries->categories->allCategories[$data->category]->constraints;
		/** @var iterable<iterable<string, mixed>> */
		$persons = $data->persons;
		foreach ($constraints as $constraint) {
			if (!$constraint->admits($persons)) {
				$categoryField->addError($constraint->getErrorMessage());
			}
		}
	}
}
