<?php

declare(strict_types=1);

namespace App\Components;

use App\Helpers\Iter;
use App\Locale\Translated;
use App\Model\Configuration\Entries;
use App\Model\Configuration\Fields;
use App\Model\Configuration\Fields\Field;
use App\Model\InputModifier;
use Contributte\Translation\Wrappers\Message;
use Contributte\Translation\Wrappers\NotTranslate;
use Kdyby\Replicator\Container as ReplicatorContainer;
use Nette\Application\UI;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Rules;
use Nette\Localization\Translator;
use Nette\Utils\Json;
use Nextras\FormComponents\Controls\DateControl;
use stdClass;

/**
 * Form for creating and editing teams.
 */
final class TeamForm extends UI\Form {
	public function __construct(
		private readonly Translator $translator,
		/** @var string[] */
		private readonly array $countries,
		/** @var array<string, int> */
		private readonly array $reservationStats,
		private readonly Entries $entries,
		private readonly bool $canModifyLocked,
		bool $isEditing,
	) {
		parent::__construct();

		$defaultMinMembers = $this->entries->minMembers;
		$defaultMaxMembers = $this->entries->maxMembers;
		// Handled in TeamPresenter::renderCreate.
		$initialMembers = $this->entries->minMembers;

		$this->addProtection();
		$this->addGroup('messages.team.info.label');
		$this->addText('name', 'messages.team.name.label')->setRequired();

		$category = new CategoryEntry('messages.team.category.label', $this->entries);
		$category->setRequired();
		$this['category'] = $category;

		$rule = $category->addCondition(true); // not to block the export of rules to JS
		$rule->addRule(function(CategoryEntry $entry) use ($defaultMaxMembers): bool {
			$category = $entry->getValue();
			$maxMembers = $this->entries->categories->allCategories[$category]->maxMembers ?? $defaultMaxMembers;
			/** @var ReplicatorContainer */
			$replicator = $entry->form['persons'];

			return iterator_count($replicator->getContainers()) <= $maxMembers;
		}, 'messages.team.error.too_many_members_simple'); // TODO: add params like in add/remove buttons

		$rule = $category->addCondition(true); // not to block the export of rules to JS
		$rule->addRule(function(CategoryEntry $entry) use ($defaultMinMembers): bool {
			$category = $entry->getValue();
			$minMembers = $this->entries->categories->allCategories[$category]->minMembers ?? $defaultMinMembers;
			/** @var ReplicatorContainer */
			$replicator = $entry->form['persons'];

			return iterator_count($replicator->getContainers()) >= $minMembers;
		}, 'messages.team.error.too_few_members_simple');

		$fields = $this->entries->teamFields;
		$this->addCustomFields(
			$fields,
			$this,
			fn(BaseControl $control): BaseControl => $control,
		);

		$this->addTextArea('message', 'messages.team.message.label');

		$this->setCurrentGroup();
		$this->addSubmit('save', $isEditing ? 'messages.team.action.edit' : 'messages.team.action.register');
		$this->addSubmit('add', 'messages.team.action.add')->setValidationScope([])->onClick[] = function(SubmitButton $button) use ($defaultMaxMembers): void {
			$category = $button->form->getUnsafeValues(null)['category'];
			$maxMembers = $this->entries->categories->allCategories[$category]->maxMembers ?? $defaultMaxMembers;
			/** @var ReplicatorContainer */
			$replicator = $button->form['persons'];
			if (iterator_count($replicator->getContainers()) < $maxMembers) {
				$replicator->createOne();
			} else {
				$button->form->addError($this->translator->translate('messages.team.error.too_many_members', $maxMembers, ['category' => $category]), false);
			}
		};
		$this->addSubmit('remove', 'messages.team.action.remove')->setValidationScope([])->onClick[] = function(SubmitButton $button) use ($defaultMinMembers): void {
			$category = $button->form->getUnsafeValues(null)['category'];
			$minMembers = $this->entries->categories->allCategories[$category]->minMembers ?? $defaultMinMembers;
			/** @var ReplicatorContainer */ // For PHPStan.
			$replicator = $button->form['persons'];
			if (iterator_count($replicator->getContainers()) > $minMembers) {
				$lastPerson = Iter::last($replicator->getContainers());
				if ($lastPerson !== null) {
					$replicator->remove($lastPerson, true);
				}
			} else {
				$button->form->addError($this->translator->translate('messages.team.error.too_few_members', $minMembers, ['category' => $category]), false);
			}
		};

		$fields = $this->entries->personFields;
		$i = 0;
		$persons = new ReplicatorContainer(
			factory: function(Container $container) use (&$i, $fields): void {
				++$i;
				$group = $this->addGroup();
				$group->setOption('label', new Message('messages.team.person.label', $i));
				$container->setCurrentGroup($group);

				$whenNotPlaceholder = fn(BaseControl $control): BaseControl => $control;
				if ($this->entries->allowPlaceholders) {
					$placeholder = $container->addCheckbox('placeholder', 'messages.team.person.is_placeholder.label');
					$whenNotPlaceholder = fn(BaseControl $control): Rules => $control->addConditionOn($placeholder, self::Equal, false);
				}

				$firstname = $container->addText('firstname', 'messages.team.person.name.first.label');
				$whenNotPlaceholder($firstname)->setRequired();
				$lastname = $container->addText('lastname', 'messages.team.person.name.last.label');
				$whenNotPlaceholder($lastname)->setRequired();
				$gender = $container->addRadioList('gender', 'messages.team.person.gender.label', ['female' => 'messages.team.person.gender.female', 'male' => 'messages.team.person.gender.male'])->setDefaultValue('male');
				$whenNotPlaceholder($gender)->setRequired();

				$birth = new DateControl('messages.team.person.birth.label');
				$whenNotPlaceholder($birth)->setRequired();
				$birth->addRule($this::MAX, 'messages.team.person.birth.error.born_too_late', $this->entries->eventDate);
				$container['birth'] = $birth;

				$this->addCustomFields($fields, $container, $whenNotPlaceholder);

				$email = $container->addEmail('email', 'messages.team.person.email.label');

				if ($i === 1) {
					// Required even for placeholder members to prevent lockout.
					$email->setRequired();
					$group->setOption('description', 'messages.team.person.isContact');
				}
			},
			createDefault: $initialMembers,
			forceDefault: true,
		);
		$this['persons'] = $persons;

		$this->onRender[] = $this->updatePersonButtonsState(...);
		$this->onValidate[] = $this->checkCategoryConstraints(...);
	}

	private function updatePersonButtonsState(): void {
		/** @var ReplicatorContainer */
		$persons = $this['persons'];
		$count = iterator_count($persons->getContainers());
		$minMembers = $this->entries->minMembers;
		$maxMembers = $this->entries->maxMembers;

		if ($count >= $maxMembers) {
			/** @var SubmitButton */
			$add = $this['add'];
			$add->setDisabled();
		}

		if ($count <= $minMembers) {
			/** @var SubmitButton */
			$remove = $this['remove'];
			$remove->setDisabled();
		}
	}

	/**
	 * @param callable(BaseControl): (BaseControl|Rules) $whenNotPlaceholder
	 */
	private function addCustomFields(array $fields, Container $container, callable $whenNotPlaceholder): void {
		foreach ($fields as $field) {
			$name = $field->name;
			$label = $field->label;

			if ($field instanceof Fields\SportidentField) {
				$input = $this->addSportident($name, $container, $whenNotPlaceholder);
			} elseif ($field instanceof Fields\CountryField) {
				$input = $container->addSelect($name, $label, $this->countries)->setPrompt('messages.team.person.country.default');
				$whenNotPlaceholder($input)->setRequired();
				if ($field->default !== null) {
					$input->setDefaultValue($field->default);
				}
			} elseif ($field instanceof Fields\PhoneField) {
				$input = $container->addText($name, $label)->setHtmlType('tel');
				$whenNotPlaceholder($input)->setRequired();
			} elseif ($field instanceof Fields\EnumField) {
				$input = $this->addEnum($name, $container, $field);
				$whenNotPlaceholder($input)->setRequired();
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
				$input = $container->addText($name, $label);
				$whenNotPlaceholder($input)->setRequired();
			}

			if ($field->description !== null) {
				$input->setOption('description', $field->description);
			}

			$isDisabled = $field->disabled ?? false;
			if ($field instanceof Fields\CheckboxlistField || $field instanceof Fields\EnumField) {
				\assert($input instanceof BootstrapCheckboxList || $input instanceof BootstrapRadioList);
				$options = $field instanceof Fields\EnumField ? $field->options : $field->items;
				$disabledFields = array_keys(
					array_filter(
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
					)
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
				$inputModifier::modify($input, $container, $whenNotPlaceholder);
			}
		}
	}

	/**
	 * @param callable(BaseControl): (BaseControl|Rules) $whenNotPlaceholder
	 */
	private function addSportident(string $name, Container $container, callable $whenNotPlaceholder): SportidentControl {
		$recommendedCardCapacity = $this->entries->recommendedCardCapacity;

		$si = new SportidentControl('messages.team.person.si.label', $recommendedCardCapacity, $whenNotPlaceholder);
		$container->addComponent($si, $name);

		return $si;
	}

	private function addEnum(string $name, Container $container, Fields\EnumField $field): BootstrapRadioList {
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

	private function checkCategoryConstraints(self $form, stdClass $data): void {
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
