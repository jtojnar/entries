<?php

namespace App\Components;

use Nette;
use App\Model\CategoryData;
use Nette\Application\UI;
use Nette\ComponentModel\IContainer;
use Nette\Diagnostics\Debugger;
use Nette\Forms\Controls\SubmitButton;
use App\Presenters\BasePresenter;
use Nette\Forms\Container;
use Nette\Forms\Form;
use Nette\Utils\Html;
use Nette\Utils\Callback;

class TeamForm extends UI\Form {
	/** @var array */
	private $countries;

	/** @var CategoryData */
	private $categories;

	public function __construct(array $countries, CategoryData $categories, $parent, $name) {
		parent::__construct($parent, $name);
		$this->countries = $countries;
		$this->categories = $categories;

		$minMembers = $this->getPresenter()->context->parameters['entries']['minMembers'];

		$this->setTranslator($parent->translator);
		$renderer = new \Nextras\Forms\Rendering\Bs3FormRenderer();
		$this->setRenderer($renderer);

		$this->addProtection();
		$this->addGroup('messages.team.info.label');
		$this->addText('name', 'messages.team.name.label')->setRequired();

		$category = new CategoryEntry('messages.team.category.label', $this->categories);
		$category->setRequired();
		$this['category'] = $category;

		if ($category->value !== null) {
			$constraints = $this->categories->getCategoryData()[$category->value]['constraints'];
			foreach ($constraints as $constraint) {
				$category->addRule(...$constraint);
			}
		}

		/** @var \Kdyby\Translation\Translator $translator */
		$translator = $this->getTranslator();

		$fields = $this->getPresenter()->context->parameters['entries']['fields']['team'];
		$this->addCustomFields($fields, $this);

		$this->addTextArea('message', 'messages.team.message.label');

		$this->setCurrentGroup();
		$this->addSubmit('save', 'messages.team.action.register');
		$this->addSubmit('add', 'messages.team.action.add')->setValidationScope(false)->onClick[] = Callback::closure($this, 'addMemberClicked');
		$this->addSubmit('remove', 'messages.team.action.remove')->setValidationScope(false)->onClick[] = Callback::closure($this, 'removeMemberClicked');

		$fields = $this->getPresenter()->context->parameters['entries']['fields']['person'];
		$i = 0;
		$this->addDynamic('persons', function(Container $container) use (&$i, $fields, $translator) {
			++$i;
			$group = $this->addGroup();
			$group->setOption('label', Html::el()->setText($translator->translate('messages.team.person.label', $i)));
			$container->setCurrentGroup($group);
			$container->addText('firstname', 'messages.team.person.name.first.label')->setRequired();

			$container->addText('lastname', 'messages.team.person.name.last.label')->setRequired();
			$container->addRadioList('gender', 'messages.team.person.gender.label', array('female' => 'messages.team.person.gender.female', 'male' => 'messages.team.person.gender.male'))->setDefaultValue('male')->setRequired();

			$this->addCustomFields($fields, $container);

			$container->addText('email', 'messages.team.person.email.label')->setType('email');
			$container->addDatePicker('birth', 'messages.team.person.birth.label')->setRequired();

			if ($i === 1) {
				$container['email']->setRequired()->addRule(Form::EMAIL);
				$container->currentGroup->setOption('description', 'messages.team.person.isContact');
			}
		}, $minMembers, true);
	}

	public function onRender() {
		$count = iterator_count($this['persons']->getContainers());
		$minMembers = $this->getPresenter()->context->parameters['entries']['minMembers'];
		$maxMembers = $this->getPresenter()->context->parameters['entries']['maxMembers'];

		if ($count >= $maxMembers) {
			$this['add']->setDisabled();
		}

		if ($count <= $minMembers) {
			$this['remove']->setDisabled();
		}
	}

	public function addMemberClicked(SubmitButton $button) {
		$button->parent['persons']->createOne();
	}

	public function removeMemberClicked(SubmitButton $button) {
		$lastPerson = null;
		foreach ($button->parent['persons']->getContainers() as $p) {
			$lastPerson = $p;
		}
		if ($lastPerson) {
			$button->parent['persons']->remove($lastPerson, true);
		}
	}

	public function addCustomFields($fields, $container) {
		/** @var BasePresenter $presenter */
		$presenter = $this->getPresenter();
		$locale = $presenter->locale;

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
					$input = $this->addText($name, $label)->setType('tel')->setRequired();
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
					$input = $this->addText($name, $label)->setRequired();
				}

				if (isset($field['description'])) {
					$input->setOption('description', $field['description'][$locale]);
				}
			}
		}
	}

	public function addSportident($name, $container) {
		$si = $container->addText($name, 'messages.team.person.si.label')->setType('number');
		$input = $container->addCheckBox($name . 'Needed', 'messages.team.person.si.rent');
		$container[$name]->addConditionOn($container[$name . 'Needed'], Form::EQUAL, false)->addRule(Form::FILLED)->addRule(Form::INTEGER);
		$container[$name . 'Needed']->addCondition(Form::EQUAL, true)->toggle($container[$name]->htmlId, false);

		return $input;
	}

	public function addEnum($name, $container, $field) {
		/** @var BasePresenter $presenter */
		$presenter = $this->getPresenter();
		$locale = $presenter->locale;

		if (isset($field['label'][$locale])) {
			$label = Html::el()->setText($field['label'][$locale]);
		} else {
			$label = $name . ':';
		}
		$options = array_map(function($option) use ($locale) {
			return $option['label'][$locale];
		}, $field['options']);

		$default = $this->getDefaultFieldValue($field);

		return $container->addRadioList($name, $label, $options)->setDefaultValue($default)->setDisabled($field['disabled'] ?? false);
	}

	public function isFieldDisabled($field) {
		return $field['disabled'] ?? false;
	}

	public function getDefaultFieldValue($field) {
		if ($field['type'] == 'enum') {
			$full_options = $field['options'];

			return array_reduce(array_keys($field['options']), function($carry, $key) use ($full_options) {
				$item = $full_options[$key];
				if (isset($item['default']) && $item['default'] === true) {
					return $key;
				}

				return $carry;
			});
		} elseif ($field['type'] == 'checkboxlist') {
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
