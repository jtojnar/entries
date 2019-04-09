<?php

declare(strict_types=1);

namespace App\Forms;

use App\Components\CategoryEntry;
use App\Components\TeamForm;
use App\Model\CategoryData;
use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Html;
use Nextras\Forms\Rendering\Bs4FormRenderer;
use function nspl\a\last;

final class TeamFormFactory {
	use Nette\SmartObject;

	/** @var Translator */
	private $translator;

	public function __construct(Translator $translator) {
		$this->translator = $translator;
	}

	public function create(array $countries, CategoryData $categories, array $parameters, string $locale, IContainer $parent = null, string $name = null): TeamForm {
		$form = new TeamForm($countries, $parameters, $locale, $parent, $name);

		$form->setTranslator($this->translator);
		$renderer = new Bs4FormRenderer();
		$form->setRenderer($renderer);

		$minMembers = $parameters['minMembers'];

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

		$fields = $parameters['fields']['team'];
		$form->addCustomFields($fields, $form);

		$form->addTextArea('message', 'messages.team.message.label');

		$form->setCurrentGroup();
		$form->addSubmit('save', 'messages.team.action.register');
		$form->addSubmit('add', 'messages.team.action.add')->setValidationScope([])->onClick[] = function(SubmitButton $button): void {
			$button->parent['persons']->createOne();
		};
		$form->addSubmit('remove', 'messages.team.action.remove')->setValidationScope([])->onClick[] = function(SubmitButton $button): void {
			$lastPerson = last($button->parent['persons']->getContainers());
			if ($lastPerson) {
				$button->parent['persons']->remove($lastPerson, true);
			}
		};

		$fields = $parameters['fields']['person'];
		$i = 0;
		$form->addDynamic('persons', function(Container $container) use (&$i, $fields, $form): void {
			++$i;
			$group = $form->addGroup();
			$group->setOption('label', Html::el()->setText($this->translator->translate('messages.team.person.label', $i)));
			$container->setCurrentGroup($group);
			$container->addText('firstname', 'messages.team.person.name.first.label')->setRequired();

			$container->addText('lastname', 'messages.team.person.name.last.label')->setRequired();
			$container->addRadioList('gender', 'messages.team.person.gender.label', ['female' => 'messages.team.person.gender.female', 'male' => 'messages.team.person.gender.male'])->setDefaultValue('male')->setRequired();

			$container->addDatePicker('birth', 'messages.team.person.birth.label')->setRequired();

			$form->addCustomFields($fields, $container);

			$container->addText('email', 'messages.team.person.email.label')->setType('email');

			if ($i === 1) {
				$container['email']->setRequired()->addRule(Form::EMAIL);
				$group->setOption('description', 'messages.team.person.isContact');
			}
		}, $minMembers, true);

		return $form;
	}
}
