<?php

declare(strict_types=1);

namespace App\Forms;

use App\Components\CategoryEntry;
use App\Components\TeamForm;
use App\Model\CategoryData;
use Contributte\Translation\Wrappers\Message;
use Nette;
use Nette\Application\UI\Form;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Localization\ITranslator;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use function nspl\a\last;

final class TeamFormFactory {
	use Nette\SmartObject;

	/** @var ITranslator */
	private $translator;

	public function __construct(ITranslator $translator) {
		$this->translator = $translator;
	}

	public function create(array $countries, CategoryData $categories, array $parameters, string $locale, bool $editing = false, IContainer $parent = null, string $name = null): TeamForm {
		$form = new TeamForm($countries, $parameters, $locale, $parent, $name);

		$form->setTranslator($this->translator);
		$renderer = new Bs4FormRenderer();
		$form->setRenderer($renderer);

		$defaultMinMembers = $parameters['minMembers'];
		$defaultMaxMembers = $parameters['maxMembers'];
		$initialMembers = $form->isSubmitted() || $editing ? $defaultMinMembers : ($parameters['initialMembers'] ?? $defaultMinMembers);

		$form->addProtection();
		$form->addGroup('messages.team.info.label');
		$form->addText('name', 'messages.team.name.label')->setRequired();

		$category = new CategoryEntry('messages.team.category.label', $categories);
		$category->setRequired();
		$form['category'] = $category;

		if ($category->value !== null) {
			$constraints = $categories->getCategoryData()[$category->value]['constraints'];
			foreach ($constraints as $constraint) {
				$category->addRule(...$constraint);
			}
		}

		$category->addRule(function(CategoryEntry $entry) use ($categories, $defaultMaxMembers) {
			$category = $entry->getValue();
			$maxMembers = $categories->getCategoryData()[$category]['maxMembers'] ?? $defaultMaxMembers;
			/** @var \Kdyby\Replicator\Container */
			$replicator = $entry->form['persons'];

			return iterator_count($replicator->getContainers()) <= $maxMembers;
		}, 'messages.team.error.too_many_members_simple'); // TODO: add params like in add/remove buttons

		$category->addRule(function(CategoryEntry $entry) use ($categories, $defaultMinMembers) {
			$category = $entry->getValue();
			$minMembers = $categories->getCategoryData()[$category]['minMembers'] ?? $defaultMinMembers;
			/** @var \Kdyby\Replicator\Container */
			$replicator = $entry->form['persons'];

			return iterator_count($replicator->getContainers()) >= $minMembers;
		}, 'messages.team.error.too_few_members_simple');

		$fields = $parameters['fields']['team'];
		$form->addCustomFields($fields, $form);

		$form->addTextArea('message', 'messages.team.message.label');

		$form->setCurrentGroup();
		$form->addSubmit('save', 'messages.team.action.register');
		$form->addSubmit('add', 'messages.team.action.add')->setValidationScope([])->onClick[] = function(SubmitButton $button) use ($categories, $defaultMaxMembers): void {
			$category = $button->form->values['category'];
			$maxMembers = $categories->getCategoryData()[$category]['maxMembers'] ?? $defaultMaxMembers;
			/** @var \Kdyby\Replicator\Container */
			$replicator = $button->form['persons'];
			if (iterator_count($replicator->getContainers()) < $maxMembers) {
				$replicator->createOne();
			} else {
				$button->form->addError($this->translator->translate('messages.team.error.too_many_members', $maxMembers, ['category' => $category]), false);
			}
		};
		$form->addSubmit('remove', 'messages.team.action.remove')->setValidationScope([])->onClick[] = function(SubmitButton $button) use ($categories, $defaultMinMembers): void {
			$category = $button->form->values['category'];
			$minMembers = $categories->getCategoryData()[$category]['minMembers'] ?? $defaultMinMembers;
			/** @var \Kdyby\Replicator\Container */
			$replicator = $button->form['persons'];
			if (iterator_count($replicator->getContainers()) > $minMembers) {
				$lastPerson = last($replicator->getContainers());
				if ($lastPerson) {
					$replicator->remove($lastPerson, true);
				}
			} else {
				$button->form->addError($this->translator->translate('messages.team.error.too_few_members', $minMembers, ['category' => $category]), false);
			}
		};

		$fields = $parameters['fields']['person'];
		$i = 0;
		$form->addDynamic('persons', function(Container $container) use (&$i, $fields, $form): void {
			++$i;
			$group = $form->addGroup();
			$group->setOption('label', new Message('messages.team.person.label', $i));
			$container->setCurrentGroup($group);
			$container->addText('firstname', 'messages.team.person.name.first.label')->setRequired();

			$container->addText('lastname', 'messages.team.person.name.last.label')->setRequired();
			$container->addRadioList('gender', 'messages.team.person.gender.label', ['female' => 'messages.team.person.gender.female', 'male' => 'messages.team.person.gender.male'])->setDefaultValue('male')->setRequired();

			$container['birth'] = (new DateControl('messages.team.person.birth.label'))->setRequired();

			$form->addCustomFields($fields, $container);

			$email = $container->addText('email', 'messages.team.person.email.label')->setType('email');

			if ($i === 1) {
				$email->setRequired()->addRule(Form::EMAIL);
				$group->setOption('description', 'messages.team.person.isContact');
			}
		}, $initialMembers, true);

		return $form;
	}
}
