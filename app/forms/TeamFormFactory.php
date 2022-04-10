<?php

declare(strict_types=1);

namespace App\Forms;

use App\Components\CategoryEntry;
use App\Components\TeamForm;
use App\Model\CategoryData;
use Contributte\FormMultiplier\Multiplier;
use Contributte\Translation\Wrappers\Message;
use Nette;
use Nette\Forms\Container;
use Nette\Forms\Form;
use Nette\Localization\Translator;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormsRendering\Renderers\Bs5FormRenderer;

final class TeamFormFactory {
	use Nette\SmartObject;

	/** @var CategoryData */
	private $categories;

	/** @var array */
	public $parameters;

	/** @var Translator */
	private $translator;

	public function __construct(CategoryData $categories, Nette\DI\Container $context, Translator $translator) {
		$this->categories = $categories;
		$this->parameters = $context->parameters['entries'];
		$this->translator = $translator;
	}

	public function create(array $countries, string $locale, bool $editing = false): TeamForm {
		$form = new TeamForm($countries, $this->parameters, $locale);

		$form->setTranslator($this->translator);
		$renderer = new Bs5FormRenderer();
		// We need the class to know what to hide (e.g. for applicableCategories).
		$renderer->wrappers['pair']['container'] = preg_replace('(class=")', '$0form-group ', $renderer->wrappers['pair']['container']);
		$form->setRenderer($renderer);

		$defaultMinMembers = $this->parameters['minMembers'];
		$defaultMaxMembers = $this->parameters['maxMembers'];
		$initialMembers = $this->parameters['initialMembers'] ?? $defaultMinMembers;

		// Group for top submit button, since DefaultFormRenderer renders group before all ungrouped Controls.
		$group = $form->addGroup();
		$group->setOption('container', 'div aria-hidden="true" class="visually-hidden"');
		// Browsers consider the first submit button a default submit button for use when submitting the form using Enter key.
		// Let’s add the save button to the top, to prevent the remove button of the first container from being picked.
		$form->addSubmit('save_default_submit', 'messages.team.action.register')->getControlPrototype()->setHtmlAttribute('aria-hidden', 'true')->setHtmlAttribute('tabindex', '-1');

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
			/** @var Multiplier */ // For PHPStan.
			$multiplier = $entry->form['persons'];

			return $multiplier->getCopyNumber() <= $maxMembers;
		}, 'messages.team.error.too_many_members_simple'); // TODO: add params like in add/remove buttons

		$rule = $category->addCondition(true); // not to block the export of rules to JS
		$rule->addRule(function(CategoryEntry $entry) use ($defaultMinMembers): bool {
			$category = $entry->getValue();
			$minMembers = $this->categories->getCategoryData()[$category]['minMembers'] ?? $defaultMinMembers;
			/** @var Multiplier */ // For PHPStan.
			$multiplier = $entry->form['persons'];

			return $multiplier->getCopyNumber() >= $minMembers;
		}, 'messages.team.error.too_few_members_simple');

		$fields = $this->parameters['fields']['team'];
		$form->addCustomFields($fields, $form);

		$form->addTextArea('message', 'messages.team.message.label');

		$form->setCurrentGroup();
		$renderer->primaryButton = $form->addSubmit('save', 'messages.team.action.register');

		$fields = $this->parameters['fields']['person'];
		$i = 0;
		$multiplier = $form->addMultiplier('persons', function(Container $container, TeamForm $form) use (&$i, $fields): void {
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
		}, $initialMembers, $defaultMaxMembers);
		$multiplier->onCreateComponents[] = function(Multiplier $multiplier) use ($form, $defaultMaxMembers): void {
			if (!$form->isSubmitted()) {
				return;
			}

			$category = $form->getUnsafeValues(null)['category'];
			$categoryData = $this->categories->getCategoryData()[$category];
			$maxMembers = $categoryData['maxMembers'] ?? $defaultMaxMembers;
			$count = iterator_count($multiplier->getContainers());
			if ($count >= $maxMembers) {
				$form->addError($this->translator->translate('messages.team.error.too_many_members', $maxMembers, ['category' => $category]), false);
			}
		};
		$multiplier->setMinCopies($defaultMinMembers);
		$multiplier->addCreateButton('messages.team.action.add')->setNoValidate();
		$multiplier->addRemoveButton('messages.team.action.remove');

		return $form;
	}
}
