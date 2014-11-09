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

class TeamForm extends UI\Form {
	/** @var array */
	private $countries;

	public function __construct(array $countries, $parent, $name) {
		parent::__construct($parent, $name);
		$this->countries = $countries;

		$minMembers = $this->presenter->context->parameters['entries']['minMembers'];
		$maxMembers = $this->presenter->context->parameters['entries']['maxMembers'];

		$this->setTranslator($this->parent->translator);
		$renderer = new \Nextras\Forms\Rendering\Bs3FormRenderer;
		$this->setRenderer($renderer);

		$this->addProtection();
		$this->addGroup('messages.team.info.label');
		$this->addText('name', 'messages.team.name.label')->setRequired();

		$genders = array_keys($this->presenter->context->parameters['entries']['categories']['gender']);
		if (count($genders) > 1) {
			$this->addRadioList('genderclass', 'messages.team.gender.label', array_combine($genders, array_map(function($a) {
				return 'messages.team.gender.' . $a;
			}, $genders)))->addRule(callback('\App\Components\TeamForm::genderClassValidator'), 'messages.team.error.gender_mismatch', $this)->setRequired()->setDefaultValue($genders[0]);
		}

		$translator = $this->translator;
		$ages_data = $this->presenter->context->parameters['entries']['categories']['age'];
		$ages = array_keys($ages_data);
		if (count($ages) > 1) {
			$this->addRadioList('ageclass', 'messages.team.age.label', array_combine($ages, array_map(function($a) use (&$translator, $ages_data) {
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
			->addRule(callback('\App\Components\TeamForm::ageClassValidator'), 'messages.team.error.age_mismatch', array($this, $this->presenter->context->parameters['entries']['eventDate'], $this->presenter->context->parameters['entries']['categories']['age']))
			->setDefaultValue($ages[0])
			->setOption('description', 'messages.team.age.help');
		}

		$durations = $this->presenter->context->parameters['entries']['categories']['duration'];
		if (count($durations) > 1) {
			$this->addRadioList('duration', 'messages.team.duration.label', array_combine($durations, array_map(function($d) {
				return 'messages.team.duration.' . $d;
			}, $durations)))
			->setRequired()
			->setDefaultValue($durations[0]);
		}

		$fields = $this->presenter->context->parameters['entries']['fields']['team'];
		$this->addCustomFields($fields, $this);

		$this->addTextArea('message', 'messages.team.message.label');

		$this->setCurrentGroup();
		$this->addSubmit('save', 'messages.team.action.register');
		$this->addSubmit('add', 'messages.team.action.add')->setValidationScope(false)->onClick[] = callback($this, 'addMemberClicked');
		$this->addSubmit('remove', 'messages.team.action.remove')->setValidationScope(false)->onClick[] = callback($this, 'removeMemberClicked');

		$fields = $this->presenter->context->parameters['entries']['fields']['person'];
		$i = 0;
		$this->addDynamic('persons', function(Container $container) use(&$i, $fields) {
			$i++;
			$group = $this->addGroup();
			$group->setOption('label', Html::el()->setText($this->translator->translate('messages.team.person.label', $i)));
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

		$count = $i;
		if ($this['add']->submittedBy) {
			$count++;
		} else if ($this['remove']->submittedBy) {
			$count--;
		}

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
			foreach ($button->parent['persons']->containers as $p) {
				$lastPerson = $p;
			}
			if ($lastPerson) {
				$button->parent['persons']->remove($lastPerson, TRUE);
			}
		}

	public function addCustomFields($fields, $container) {
		foreach ($fields as $name => $field) {
			if (isset($field['type'])) {
				if ($field['type'] === 'sportident') {
					$this->addSportident($name, $container);
				} else if ($field['type'] === 'country') {
					$country = $container->addSelect($name, 'messages.team.person.country.label', $this->countries)->setPrompt('messages.team.person.country.default')->setRequired();
					if (isset($field['default'])) {
						$country->setDefaultValue($field['default']);
					}
				} else if ($field['type'] === 'phone') {
					$label = isset($field['label'][$this->presenter->locale]) ? Html::el()->setText($field['label'][$this->presenter->locale]) : 'messages.team.phone.label';
					$input = $this->addText($name, $label)->setType('tel')->setRequired();
				} else {
					$label = Html::el()->setText(isset($field['label'][$this->presenter->locale]) ? $field['label'][$this->presenter->locale] : $name);
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
