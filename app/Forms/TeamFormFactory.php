<?php

declare(strict_types=1);

namespace App\Forms;

use App\Components\CategoryEntry;
use App\Components\TeamForm;
use App\Model\CategoryData;
use Contributte\Translation\Wrappers\Message;
use Kdyby\Replicator\Container as ReplicatorContainer;
use Nette;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Localization\Translator;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormsRendering\Renderers\Bs5FormRenderer;

use function nspl\a\last;

final class TeamFormFactory {
	use Nette\SmartObject;

	/** @var array */
	public $parameters;

	public function __construct(
		private CategoryData $categories,
		Nette\DI\Container $context,
		private Translator $translator,
	) {
		$this->parameters = $context->parameters['entries'];
	}

	public function create(
		array $countries,
		string $locale,
		bool $editing = false,
		/* @var array<string, int> */
		array $reservationStats = [],
		IContainer $parent = null,
		string $name = null,
	): TeamForm {
		$form = new TeamForm($countries, $this->parameters, $locale, $reservationStats, $parent, $name);

		$form->setTranslator($this->translator);
		$renderer = new Bs5FormRenderer();
		// We need the class to know what to hide (e.g. for applicableCategories).
		$renderer->wrappers['pair']['container'] = preg_replace('(class=")', '$0form-group ', $renderer->wrappers['pair']['container']);
		$form->setRenderer($renderer);

		$defaultMinMembers = $this->parameters['minMembers'];
		$defaultMaxMembers = $this->parameters['maxMembers'];
		$initialMembers = $form->isSubmitted() || $editing ? $defaultMinMembers : ($this->parameters['initialMembers'] ?? $defaultMinMembers);

		$form->addProtection();
		$form->addGroup('messages.team.info.label');
		$form->addText('name', 'messages.team.name.label')->setRequired();

		$category = new CategoryEntry('messages.team.category.label', $this->categories);
		$category->setRequired();
		$form['category'] = $category;

		if ($category->value !== null) {
			$constraints = $this->categories->getCategoryData()[$category->value]['constraints'];
			foreach ($constraints as $constraint) {
				$rule = $category->addCondition(true); // not to block the export of rules to JS
				$rule->addRule(...$constraint);
			}
		}

		$rule = $category->addCondition(true); // not to block the export of rules to JS
		$rule->addRule(function(CategoryEntry $entry) use ($defaultMaxMembers): bool {
			$category = $entry->getValue();
			$maxMembers = $this->categories->getCategoryData()[$category]['maxMembers'] ?? $defaultMaxMembers;
			/** @var ReplicatorContainer */
			$replicator = $entry->form['persons'];

			return iterator_count($replicator->getContainers()) <= $maxMembers;
		}, 'messages.team.error.too_many_members_simple'); // TODO: add params like in add/remove buttons

		$rule = $category->addCondition(true); // not to block the export of rules to JS
		$rule->addRule(function(CategoryEntry $entry) use ($defaultMinMembers): bool {
			$category = $entry->getValue();
			$minMembers = $this->categories->getCategoryData()[$category]['minMembers'] ?? $defaultMinMembers;
			/** @var ReplicatorContainer */
			$replicator = $entry->form['persons'];

			return iterator_count($replicator->getContainers()) >= $minMembers;
		}, 'messages.team.error.too_few_members_simple');

		$fields = $this->parameters['fields']['team'];
		$form->addCustomFields($fields, $form);

		$form->addTextArea('message', 'messages.team.message.label');

		$form->setCurrentGroup();
		$form->addSubmit('save', 'messages.team.action.register');
		$form->addSubmit('add', 'messages.team.action.add')->setValidationScope([])->onClick[] = function(SubmitButton $button) use ($defaultMaxMembers): void {
			$category = $button->form->getUnsafeValues(null)['category'];
			$maxMembers = $this->categories->getCategoryData()[$category]['maxMembers'] ?? $defaultMaxMembers;
			/** @var ReplicatorContainer */
			$replicator = $button->form['persons'];
			if (iterator_count($replicator->getContainers()) < $maxMembers) {
				$replicator->createOne();
			} else {
				$button->form->addError($this->translator->translate('messages.team.error.too_many_members', $maxMembers, ['category' => $category]), false);
			}
		};
		$form->addSubmit('remove', 'messages.team.action.remove')->setValidationScope([])->onClick[] = function(SubmitButton $button) use ($defaultMinMembers): void {
			$category = $button->form->getUnsafeValues(null)['category'];
			$minMembers = $this->categories->getCategoryData()[$category]['minMembers'] ?? $defaultMinMembers;
			/** @var ReplicatorContainer */ // For PHPStan.
			$replicator = $button->form['persons'];
			if (iterator_count($replicator->getContainers()) > $minMembers) {
				/** @var ?Container */ // For PHPStan.
				$lastPerson = last($replicator->getContainers());
				if ($lastPerson) {
					$replicator->remove($lastPerson, true);
				}
			} else {
				$button->form->addError($this->translator->translate('messages.team.error.too_few_members', $minMembers, ['category' => $category]), false);
			}
		};

		$fields = $this->parameters['fields']['person'];
		$i = 0;
		$form->addDynamic('persons', function(Container $container) use (&$i, $fields, $form): void {
			++$i;
			$group = $form->addGroup();
			$group->setOption('label', new Message('messages.team.person.label', $i));
			$container->setCurrentGroup($group);
			$container->addText('firstname', 'messages.team.person.name.first.label')->setRequired();

			$container->addText('lastname', 'messages.team.person.name.last.label')->setRequired();
			$container->addRadioList('gender', 'messages.team.person.gender.label', ['female' => 'messages.team.person.gender.female', 'male' => 'messages.team.person.gender.male'])->setDefaultValue('male')->setRequired();

			$birth = new DateControl('messages.team.person.birth.label');
			$birth->setRequired();
			$birth->addRule($form::MAX, 'messages.team.person.birth.error.born_too_late', $this->parameters['eventDate']);
			$container['birth'] = $birth;

			$form->addCustomFields($fields, $container);

			$email = $container->addEmail('email', 'messages.team.person.email.label');

			if ($i === 1) {
				$email->setRequired();
				$group->setOption('description', 'messages.team.person.isContact');
			}
		}, $initialMembers, true);

		return $form;
	}
}
