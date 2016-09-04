<?php

namespace App\Components;

use Nette;
use App;
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

	public function __construct(array $countries, $parent, $name) {
		parent::__construct($parent, $name);
		$this->countries = $countries;

		$minMembers = $this->getPresenter()->context->parameters['entries']['minMembers'];

		$this->setTranslator($this->parent->translator);
		$renderer = new \Nextras\Forms\Rendering\Bs3FormRenderer;
		$this->setRenderer($renderer);

		$this->addProtection();
		$this->addGroup('messages.team.info.label');
		$this->addText('name', 'messages.team.name.label')->setRequired();

		$genders_data = $this->getPresenter()->context->parameters['entries']['categories']['gender'];
		$genders = array_keys($genders_data);
		if (count($genders) > 1) {
			$this->addRadioList('genderclass', 'messages.team.gender.label', array_combine($genders, array_map(function($a) use ($genders_data) {
				if (isset($genders_data[$a]['label']) && isset($genders_data[$a]['label'][$this->getPresenter()->locale])) {
					return Html::el()->setText($genders_data[$a]['label'][$this->getPresenter()->locale]);
				}

				return 'messages.team.gender.' . $a;
			}, $genders)))->addRule(\App\Components\TeamForm::genderClassValidator, 'messages.team.error.gender_mismatch', $this)->setRequired()->setDefaultValue($genders[0]);
		}

		$translator = $this->getTranslator();
		$ages_data = $this->getPresenter()->context->parameters['entries']['categories']['age'];
		$ages = array_keys($ages_data);
		if (count($ages) > 1) {
			$this->addRadioList('ageclass', 'messages.team.age.label', array_combine($ages, array_map(function($a) use (&$translator, $ages_data) {
				if (isset($ages_data[$a]['label']) && isset($ages_data[$a]['label'][$this->getPresenter()->locale])) {
					return Html::el()->setText($ages_data[$a]['label'][$this->getPresenter()->locale]);
				}

				$info = '';
				if (isset($ages_data[$a]['min'])) {
					$info .= ' ' . $translator->translate('messages.team.age.min', null, array('age' => $ages_data[$a]['min']));
				}

				if (isset($ages_data[$a]['max'])) {
					$info .= ' ' . $translator->translate('messages.team.age.max', null, array('age' => $ages_data[$a]['max']));
				}

				return Html::el()->setText($translator->translate('messages.team.age.' . $a) . $info);
			}, $ages)))
			->setRequired()
			->addRule(\App\Components\TeamForm::ageClassValidator, 'messages.team.error.age_mismatch', array($this, $this->getPresenter()->context->parameters['entries']['eventDate'], $this->getPresenter()->context->parameters['entries']['categories']['age']))
			->setDefaultValue($ages[0])
			->setOption('description', 'messages.team.age.help');
		}

		$durations = $this->getPresenter()->context->parameters['entries']['categories']['duration'];
		if (count($durations) > 1) {
			$this->addRadioList('duration', 'messages.team.duration.label', array_combine($durations, array_map(function($d) {
				return 'messages.team.duration.' . $d;
			}, $durations)))
			->setRequired()
			->setDefaultValue($durations[0]);
		}

		$fields = $this->getPresenter()->context->parameters['entries']['fields']['team'];
		$this->addCustomFields($fields, $this);

		$this->addTextArea('message', 'messages.team.message.label');

		$this->setCurrentGroup();
		$this->addSubmit('save', 'messages.team.action.register');
		$this->addSubmit('add', 'messages.team.action.add')->setValidationScope(false)->onClick[] = Callback::closure($this, 'addMemberClicked');
		$this->addSubmit('remove', 'messages.team.action.remove')->setValidationScope(false)->onClick[] = Callback::closure($this, 'removeMemberClicked');

		$fields = $this->getPresenter()->context->parameters['entries']['fields']['person'];
		$i = 0;
		$this->addDynamic('persons', function(Container $container) use(&$i, $fields, $translator) {
			$i++;
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
				$button->parent['persons']->remove($lastPerson, TRUE);
			}
		}

	public function addCustomFields($fields, $container) {
		foreach ($fields as $name => $field) {
			if (isset($field['type'])) {
				if (isset($field['label'][$this->getPresenter()->locale])) {
					$label = Html::el()->setText($field['label'][$this->getPresenter()->locale]);
				} else if ($field['type'] === 'country') {
					$label = 'messages.team.person.country.label';
				} else if ($field['type'] === 'phone') {
					$label = 'messages.team.phone.label';
				} else if ($field['type'] === 'sportident') {
					$label = 'messages.team.person.si.label';
				} else {
					$label = $name . ':';
				}

				if ($field['type'] === 'sportident') {
					$this->addSportident($name, $container);
				} else if ($field['type'] === 'country') {
					$country = $container->addSelect($name, $label, $this->countries)->setPrompt('messages.team.person.country.default')->setRequired();
					if (isset($field['default'])) {
						$country->setDefaultValue($field['default']);
					}
				} else if ($field['type'] === 'phone') {
					$input = $this->addText($name, $label)->setType('tel')->setRequired();
				} else if ($field['type'] === 'enum') {
					$input = $this->addEnum($name, $container, $field)->setRequired();
				} else {
					$input = $this->addText($name, $label)->setRequired();
				}
			}
		}
	}

	public function addSportident($name, $container) {
		$si = $container->addText($name, 'messages.team.person.si.label')->setType('number');
		$container->addCheckBox($name . 'Needed', 'messages.team.person.si.rent');
		$container[$name]->addConditionOn($container[$name . 'Needed'], Form::EQUAL, false)->addRule(Form::FILLED)->addRule(Form::INTEGER);
		$container[$name . 'Needed']->addCondition(Form::EQUAL, true)->toggle($container[$name]->htmlId, false);
	}

	public function addEnum($name, $container, $field) {
		if (isset($field['label'][$this->getPresenter()->locale])) {
			$label = Html::el()->setText($field['label'][$this->getPresenter()->locale]);
		} else {
			$label = $name . ':';
		}
		$options = array_map(function($option) {
			return $option['label'][$this->getPresenter()->locale];
		}, $field['options']);

		$full_options = $field['options'];

		$default = array_reduce(array_keys($field['options']), function($carry, $key) use ($full_options) {
			$item = $full_options[$key];
			if(isset($item['default']) && $item['default'] === true) {
				return $key;
			}
			return $carry;
		});
		return $container->addRadioList($name, $label, $options)->setDefaultValue($default);
	}

	public static function genderClassValidator(Nette\Forms\IControl $input, Nette\Forms\Form $form) {
		$male = false;
		$female = false;
		$class = $input->value;
		foreach ($form['persons']->values as $person) {
			if ($person['firstname']) {
				if ($person['gender'] == 'male') {
					$male = true;
				} else {
					$female = true;
				}
			}
		}
		if (($male && !$female && $class == 'male') || (!$male && $female && $class == 'female') || ($male && $female && $class == 'mixed') || !in_array($class, ['male', 'female', 'mixed'])) {
			return true;
		}
		return false;
	}


	public static function ageClassValidator(Nette\Forms\IControl $input, array $args) {
		list($form, $eventDate, $ages_data) = $args;
		$class = $input->value;
		$ages = array();

		foreach ($form['persons']->values as $person) {
			if ($person['firstname']) {
				if ($person['birth']) {
					$diff = $person['birth']->diff($eventDate, true)->y;
					$ages[] = $diff;
				} else {
					return false;
				}
			}
		}
		return self::validateAgeClass($class, $ages, $ages_data);
	}


	public static function validateAgeClass($class, $ages, $classes) {
		if (!isset($classes[$class])) {
			throw new Exception('Validating against unknown age class');
		}

		$min = isset($classes[$class]['min']) ? $classes[$class]['min'] : null;
		$max = isset($classes[$class]['max']) ? $classes[$class]['max'] : null;

		if ($min || $max) {
			foreach ($ages as $age) {
				if ($max && $age > $max) {
					return false;
				}
				if ($min && $age < $min) {
					return false;
				}
			}
		}
		return true;
	}
}
