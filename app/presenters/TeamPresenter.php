<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Components\SportidentControl;
use App\Exporters;
use App\Model\Invoice;
use Closure;
use Exception;
use Latte;
use Money\Currency;
use Money\Money;
use Nette;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Mail\Message;
use Nette\Utils\DateTime;
use Nextras\FormsRendering\Renderers\FormLayout;
use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;
use Tracy\Debugger;

/**
 * The main presenter of the application.
 */
class TeamPresenter extends BasePresenter {
	/** @var App\Model\CountryRepository @inject */
	public $countries;

	/** @var App\Model\TeamRepository @inject */
	public $teams;

	/** @var App\Model\PersonRepository @inject */
	public $persons;

	/** @var App\Model\InvoiceRepository @inject */
	public $invoices;

	/** @var App\Model\CategoryData @inject */
	public $categories;

	/** @var App\Templates\Filters\CategoryFormatFilter @inject */
	public $categoryFormatter;

	/** @var App\Forms\FormFactory @inject */
	public $formFactory;

	/** @var App\Forms\TeamFormFactory @inject */
	public $teamFormFactory;

	/** @var Nette\Security\Passwords @inject */
	public $passwords;

	/** @var \Nette\Http\Request @inject */
	public $request;

	/** @var \Nette\Http\Response @inject */
	public $response;

	/** @var \Nette\Mail\Mailer @inject */
	public $mailer;

	/** @var Nette\DI\Container @inject */
	public $context;

	public function startup(): void {
		if (($this->action === 'register' || $this->action === 'edit') && !$this->user->isInRole('admin')) {
			if ($this->context->parameters['entries']['closing']->diff(new DateTime())->invert === 0) {
				throw new App\TooLateForAccessException();
			} elseif ($this->context->parameters['entries']['opening']->diff(new DateTime())->invert === 1) {
				throw new App\TooSoonForAccessException();
			}
		}
		parent::startup();
	}

	public function actionList(): void {
		$where = [];
		$category = $this->request->getQuery('category');
		if ($category !== null) {
			$where = ['category' => explode('|', $category)];
		}

		if ($this->request->getQuery('status') !== null) {
			switch ($this->request->getQuery('status')) {
				case 'paid':
					$where['status'] = 'paid';
					break;
				case 'registered':
					$where['status'] = 'registered';
					break;
			}
		}

		if (!isset($where['status'])) {
			$where['status!='] = 'withdrawn';
		}

		/** @var \Nette\Bridges\ApplicationLatte\DefaultTemplate $template */
		$template = $this->template;

		$template->teams = $this->teams->findBy($where);
		$template->countries = $this->countries;

		$template->getLatte()->addFilter('personData', Closure::fromCallable([$this, 'personData']));
		$template->getLatte()->addFilter('teamData', Closure::fromCallable([$this, 'teamData']));

		$template->stats = ['count' => \count($template->teams)];
	}

	public function renderEdit(int $id = null): void {
		if (!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['return' => 'edit']);
		} else {
			/** @var \Nette\Security\SimpleIdentity $identity */
			$identity = $this->user->identity;

			if ($id === null) {
				$this->redirect('edit', ['id' => $identity->id]);
			}
			if (!$this->user->isInRole('admin') && $identity->id !== $id) {
				$backlink = $this->storeRequest('+ 48 hours');
				$this->redirect('Sign:in', ['backlink' => $backlink]);
			}

			$team = $this->teams->getById($id);
			if (!$team) {
				$this->error($this->translator->translate('messages.team.edit.error.404'));
			}
			if (!$this->user->isInRole('admin') && $team->status === 'paid') {
				$this->flashMessage($this->translator->translate('messages.team.edit.error.already_paid'), 'error');
				$this->redirect('Homepage:');
			}
		}
	}

	public function actionConfirm(int $id): void {
		if ($this->user->isInRole('admin')) {
			$team = $this->teams->getById($id);
			if (!$team) {
				$this->error($this->translator->translate('messages.team.edit.error.404'));
			}
			if ($team->status === 'registered') {
				$team->status = 'paid';
				$team->lastInvoice->status = Invoice::STATUS_PAID;
				$this->teams->persistAndFlush($team);
				$this->redirect('list');
			} else {
				$this->flashMessage($this->translator->translate('messages.team.edit.error.already_paid'), 'info');
				$this->redirect('Homepage:');
			}
		} else {
			$backlink = $this->storeRequest('+ 48 hours');
			$this->redirect('Sign:in', ['backlink' => $backlink]);
		}
	}

	public function actionExport(string $type = 'csv'): void {
		if (!$this->user->isInRole('admin')) {
			$backlink = $this->storeRequest('+ 48 hours');
			$this->redirect('Sign:in', ['backlink' => $backlink]);
		}

		$where = [];

		$category = $this->request->getQuery('category');
		if ($category !== null) {
			$where = ['category' => explode('|', $category)];
		}

		switch ($this->request->getQuery('status')) {
			case 'paid':
				$where['status'] = 'paid';
				break;
			case 'registered':
				$where['status'] = 'registered';
				break;
			case 'all':
				break;
			default:
				$where['status!='] = 'withdrawn';
		}

		$teams = $this->teams->findBy($where);

		$teamFields = $this->context->parameters['entries']['fields']['team'];
		$personFields = $this->context->parameters['entries']['fields']['person'];

		if (\count($teams)) {
			if ($type === 'meos') {
				$exporter = new Exporters\MeosExporter($teams, $this->categoryFormatter);
				$this->response->setContentType($exporter->getMimeType(), 'UTF-8');
				$exporter->output();
			} else {
				$exporter = new Exporters\CsvExporter($teams, $this->countries, $teamFields, $personFields, $this->categoryFormatter);
				$this->response->setContentType('text/plain', 'UTF-8');
				$exporter->output();
			}
		} else {
			$this->flashMessage('messages.team.list.empty', 'error');
			$this->redirect('list');
		}
	}

	protected function createComponentTeamForm(string $name): Form {
		$editing = $this->getParameter('id') !== null;
		$form = $this->teamFormFactory->create($this->countries->fetchIdNamePairs(), $this->locale, $editing, $this, $name);
		if ($editing && !$form->isSubmitted()) {
			$id = (int) $this->getParameter('id');
			$team = $this->teams->getById($id);
			if (!$team) {
				$this->error($this->translator->translate('messages.team.edit.error.404'));
			}
			$default = [];
			$default['name'] = $team->name;
			$default['category'] = $team->category;
			$default['message'] = $team->message;
			$default['persons'] = [];

			$fields = $this->context->parameters['entries']['fields']['team'];
			foreach ($fields as $name => $field) {
				if (isset($team->getJsonData()->$name)) {
					$default[$name] = $team->getJsonData()->$name;
				} elseif ($field['type'] === 'sportident') {
					$default[$name] = [
						SportidentControl::NAME_NEEDED => true,
					];
				}
			}

			$fields = $this->context->parameters['entries']['fields']['person'];
			foreach ($team->persons as $person) {
				$personDefault = [
					'firstname' => $person->firstname,
					'lastname' => $person->lastname,
					'gender' => $person->gender,
					'email' => $person->email,
					'birth' => $person->birth,
				];

				foreach ($fields as $name => $field) {
					if (isset($person->getJsonData()->$name)) {
						$personDefault[$name] = $person->getJsonData()->$name;
					} elseif ($field['type'] === 'sportident') {
						$personDefault[$name] = [
							SportidentControl::NAME_NEEDED => true,
						];
					}
				}

				$default['persons'][] = $personDefault;
			}
			$form->setValues($default);
		}
		/** @var \Nette\Forms\Controls\SubmitButton */
		$save = $form['save'];
		if ($this->getParameter('id')) {
			$save->caption = 'messages.team.action.edit';
		}
		/** @var callable(Nette\Forms\Controls\SubmitButton): void */
		$processTeamForm = Closure::fromCallable([$this, 'processTeamForm']);
		$save->onClick[] = $processTeamForm;

		return $form;
	}

	private function processTeamForm(Nette\Forms\Controls\SubmitButton $button): void {
		if (!$this->user->isInRole('admin')) {
			if ($this->context->parameters['entries']['closing']->diff(new DateTime())->invert === 0) {
				throw new App\TooLateForAccessException();
			} elseif ($this->context->parameters['entries']['opening']->diff(new DateTime())->invert === 1) {
				throw new App\TooSoonForAccessException();
			}
		}

		/** @var App\Components\TeamForm $form */
		$form = $button->form;
		$values = $form->getValues();
		/** @var string $password */
		$password = null;

		$this->cleanNonApplicableFields($form);

		if ($this->action === 'edit') {
			if (!$this->user->isLoggedIn()) {
				throw new ForbiddenRequestException();
			}

			$id = (int) $this->getParameter('id');
			$team = $this->teams->getById($id);

			/** @var \Nette\Security\SimpleIdentity $identity */
			$identity = $this->user->identity;

			if (!$team) {
				$form->addError('messages.team.edit.error.404');

				return;
			} elseif (!$this->user->isInRole('admin') && $team->status === 'paid') {
				$form->addError('messages.team.edit.error.already_paid');
			} elseif (!$this->user->isInRole('admin') && $identity->id !== $id) {
				$backlink = $this->storeRequest('+ 48 hours');
				$this->redirect('Sign:in', ['backlink' => $backlink]);
			}
		} else {
			$team = new App\Model\Team();
			$password = Nette\Utils\Random::generate();
			$team->password = $this->passwords->hash($password);
			$team->ip = $this->request->remoteAddress ?? '';
		}

		try {
			$invoice = new Invoice();
			$invoice->status = Invoice::STATUS_NEW;
			$invoice->team = $team;
			$invoice->items = [];

			$team->name = $values['name'];
			$team->message = $values['message'];

			$team->category = isset($form['category']) ? $values['category'] : '';

			$currency = new Currency($this->context->parameters['entries']['fees']['currency']);
			$fields = $this->context->parameters['entries']['fields']['team'];
			$jsonData = [];
			foreach ($fields as $name => $field) {
				$jsonData[$name] = $values[$name];
				$type = $field['type'];

				if ($type === 'sportident' && isset($field['fee']) && (($jsonData[$name] ?? [])[SportidentControl::NAME_NEEDED] ?? null) === true) {
					$invoice->addItem(self::serializeInvoiceItem([
						'type' => $type,
						'scope' => 'team',
						'key' => $name,
					]), new Money($field['fee'] * 100, $currency));
				} elseif ($type === 'checkbox' && isset($field['fee']) && $jsonData[$name]) {
					$invoice->addItem(self::serializeInvoiceItem([
						'type' => $type,
						'scope' => 'team',
						'key' => $name,
					]), new Money($field['fee'] * 100, $currency));
				} elseif ($type === 'enum' && isset($field['options'][$values[$name]]) && isset($field['options'][$values[$name]]['fee']) && $jsonData[$name]) {
					$invoice->addItem(self::serializeInvoiceItem([
						'type' => $type,
						'scope' => 'team',
						'key' => $name,
						'value' => $values[$name],
					]), new Money($field['options'][$values[$name]]['fee'] * 100, $currency));
				} elseif ($type === 'checkboxlist') {
					foreach ($jsonData[$name] as $item) {
						if (isset($field['items'][$item]['fee'])) {
							$invoice->addItem(self::serializeInvoiceItem([
								'type' => $type,
								'scope' => 'team',
								'key' => $name,
								'value' => $item,
							]), new Money($field['items'][$item]['fee'] * 100, $currency));
						}
					}
				}
			}
			$team->setJsonData($jsonData);

			$this->teams->persist($team);

			if ($this->action === 'edit') {
				foreach ($team->persons as $person) {
					$this->persons->remove($person);
				}
			}

			$personFee = $this->categories->getCategoryData()[$team->category]['fee'];
			$invoice->createItem(self::serializeInvoiceItem([
				'type' => '~entry',
				'scope' => 'person',
			]), new Money($personFee * 100, $currency));

			$fields = $this->context->parameters['entries']['fields']['person'];

			/** @var ?string $firstMemberAddress */
			$firstMemberAddress = null;
			/** @var ?string $firstMemberName */
			$firstMemberName = null;

			foreach ($values['persons'] as $member) {
				$firstname = $member['firstname'];
				if ($firstMemberAddress === null) {
					$firstMemberAddress = $member['email'];
				}
				if ($firstMemberName === null) {
					$firstMemberName = $member['firstname'] . ' ' . $member['lastname'];
				}
				$person = new App\Model\Person();

				$person->firstname = $firstname;
				$person->lastname = $member['lastname'];
				$person->gender = $member['gender'];
				$person->birth = $member['birth'];
				$person->email = $member['email'];
				$person->contact = \count($team->persons) === 0;
				$person->team = $team;

				$jsonData = [];
				foreach ($fields as $name => $field) {
					$member[$name] = $member[$name] ?? null;
					$jsonData[$name] = $form->isFieldDisabled($field) ? $form->getDefaultFieldValue($field) : $member[$name];
					$type = $field['type'];

					if ($type === 'sportident' && isset($field['fee']) && (($jsonData[$name] ?? [])[SportidentControl::NAME_NEEDED] ?? null) === true) {
						$invoice->addItem(self::serializeInvoiceItem([
							'type' => $type,
							'scope' => 'person',
							'key' => $name,
						]), new Money($field['fee'] * 100, $currency));
					} elseif ($type === 'checkbox' && isset($field['fee']) && $jsonData[$name]) {
						$invoice->addItem(self::serializeInvoiceItem([
							'type' => $type,
							'scope' => 'person',
							'key' => $name,
						]), new Money($field['fee'] * 100, $currency));
					} elseif ($type === 'enum' && isset($field['options'][$member[$name]]) && isset($field['options'][$member[$name]]['fee']) && $jsonData[$name]) {
						$invoice->addItem(self::serializeInvoiceItem([
							'type' => $type,
							'scope' => 'person',
							'key' => $name,
							'value' => $member[$name],
						]), new Money($field['options'][$member[$name]]['fee'] * 100, $currency));
					} elseif ($type === 'checkboxlist') {
						foreach ($jsonData[$name] as $item) {
							if (isset($field['items'][$item]['fee'])) {
								$invoice->addItem(self::serializeInvoiceItem([
									'type' => $type,
									'scope' => 'person',
									'key' => $name,
									'value' => $item,
								]), new Money($field['items'][$item]['fee'] * 100, $currency));
							}
						}
					}
				}

				$person->setJsonData($jsonData);

				$invoice->addItem(self::serializeInvoiceItem([
					'type' => '~entry',
					'scope' => 'person',
				]));
				$this->persons->persist($person);
			}

			/** @var ?callable */
			$invoiceModifier = $this->context->parameters['entries']['invoiceModifier'] ?? null;
			if ($invoiceModifier !== null) {
				$invoiceModifier($team, $invoice, $this->context->parameters['entries']);
			}

			foreach ($team->invoices as $inv) {
				if ($inv->status === Invoice::STATUS_NEW && $inv !== $invoice) {
					$inv->status = Invoice::STATUS_CANCELLED;
					$this->invoices->persist($inv);
				}
			}

			$this->invoices->persist($invoice);

			$this->teams->flush();

			if ($this->action === 'edit') {
				$this->flashMessage($this->translator->translate('messages.team.success.edit'));
			} else {
				/** @var \Nette\Bridges\ApplicationLatte\DefaultTemplate $mtemplate */
				$mtemplate = $this->createTemplate();

				$appDir = $this->context->parameters['appDir'];

				$baseMailTemplateLocalizedPath = $appDir . '/templates/Mail/verification.' . $this->locale . '.latte';

				// If the override templates exist in the config directory,
				// let’s use them.
				$mailTemplatePath = null;
				if (file_exists($appDir . '/config/mail/verification.' . $this->locale . '.latte')) {
					$mailTemplatePath = $appDir . '/config/mail/verification.' . $this->locale . '.latte';
				} elseif (file_exists($appDir . '/config/mail/verification.latte')) {
					$mailTemplatePath = $appDir . '/config/mail/verification.latte';
				}

				// If not, let’s use the built-in templates.
				$baseMailTemplatePath = file_exists($baseMailTemplateLocalizedPath) ? $baseMailTemplateLocalizedPath : $appDir . '/templates/Mail/verification.latte';
				if ($mailTemplatePath === null) {
					$mailTemplatePath = $baseMailTemplatePath;
				} else {
					// If the overrides exist, pass the built-in template as a parameter
					// so that they can inherit it and only override what they need.
					$mtemplate->layout = $baseMailTemplatePath;
					// TODO: Try to make use of coreParentFinder
					// so that the templates do not need to carry layout tags.
					// https://latte.nette.org/en/develop#toc-layout-lookup
				}

				$mtemplate->setFile($mailTemplatePath);

				// Define variables for use in the e-mail template.
				$mtemplate->accountNumber = $this->context->parameters['entries']['accountNumber'];
				$mtemplate->eventName =
					$this->parameters->getSiteTitle($this->locale)
					?? $this->parameters->getSiteTitle($this->translator->getDefaultLocale());
				$mtemplate->eventNameShort =
					$this->parameters->getSiteTitleShort($this->locale)
					?? $this->parameters->getSiteTitleShort($this->translator->getDefaultLocale())
					?? $mtemplate->eventName;
				$mtemplate->dateFormat = $this->translator->translate('messages.email.verification.entry_details.person.birth.format');
				$mtemplate->team = $team;
				$mtemplate->people = $team->persons;
				$mtemplate->id = $team->id;
				$mtemplate->name = $firstMemberName;
				$mtemplate->password = $password;
				$mtemplate->invoice = $invoice;
				$mtemplate->organiserMail = $this->context->parameters['webmasterEmail'];

				// Inline styles into the e-mail
				$mailHtml = (string) $mtemplate;
				$domDocument = CssInliner::fromHtml($mailHtml)
					->inlineCss(file_get_contents($appDir . '/templates/Mail/style.css') ?: '')
					->getDomDocument();
				HtmlPruner::fromDomDocument($domDocument)
					->removeElementsWithDisplayNone();
				$mailHtml = CssToAttributeConverter::fromDomDocument($domDocument)
					->convertCssToVisualAttributes()
					->render();

				$mail = new Message();
				$mail->setFrom($mtemplate->organiserMail)->addTo($firstMemberAddress)->setHtmlBody($mailHtml);

				$mailer = $this->mailer;
				$mailer->send($mail);

				$this->flashMessage($this->translator->translate('messages.team.success.add', null, ['password' => $password]));
			}
			$this->redirect('Homepage:');
		} catch (Nette\Application\AbortException $e) {
			throw $e;
		} catch (Exception $e) {
			Debugger::log($e);
			if ($this->action === 'edit') {
				$form->addError('messages.team.error.edit_general');
			} else {
				$form->addError('messages.team.error.add_general');
			}
		}
	}

	public function cleanNonApplicableFields(Nette\Forms\Form $form): void {
		$category = $form->values['category'];

		$teamFields = $this->context->parameters['entries']['fields']['team'];
		foreach ($teamFields as $name => $field) {
			if (isset($field['applicableCategories']) && !\in_array($category, $field['applicableCategories'], true)) {
				/** @var Nette\Forms\Controls\BaseControl */
				$control = $form[$name];
				$control->setValue(null);
			}
		}

		$personFields = $this->context->parameters['entries']['fields']['person'];
		foreach ($form->values['persons'] as $member) {
			foreach ($personFields as $name => $field) {
				if (isset($field['applicableCategories']) && !\in_array($category, $field['applicableCategories'], true)) {
					/** @var Nette\Utils\ArrayHash */
					$persons = $form['persons'];
					/** @var Nette\Forms\Controls\BaseControl */
					$control = $persons[$name];
					$control->setValue(null);
				}
			}
		}
	}

	public function createComponentTeamListFilterForm(): Form {
		$form = $this->formFactory->create(FormLayout::INLINE);
		$form->setMethod($form::GET);

		$category = $form['category'] = new App\Components\CategoryEntry('messages.team.list.filter.category.label', $this->categories, true);
		$category->setPrompt('messages.team.list.filter.category.all');
		$category->setHtmlAttribute('style', 'width:auto;');

		if ($this->request->getQuery('category')) {
			$category->setValue($this->request->getQuery('category'));
		}
		$category->controlPrototype->class[] = 'change-form-submit';

		if ($this->user->isInRole('admin')) {
			$status = $form->addSelect('status', 'messages.team.list.filter.status.label', ['registered' => 'messages.team.list.filter.status.registered', 'paid' => 'messages.team.list.filter.status.paid'])->setPrompt('messages.team.list.filter.status.all')->setHtmlAttribute('style', 'width:auto;');
			if ($this->request->getQuery('status')) {
				$status->setValue($this->request->getQuery('status'));
			}
			$status->controlPrototype->class[] = 'change-form-submit';
		}

		$submit = $form->addSubmit('filter', 'messages.team.list.filter.submit.label');
		$submit->controlPrototype->class[] = 'noscript';
		/** @var callable(Nette\Forms\Container): void */
		$filterRedir = Closure::fromCallable([$this, 'filterRedir']);
		$form->onValidate[] = $filterRedir;

		return $form;
	}

	private function filterRedir(Nette\Forms\Form $form): void {
		$parameters = [];

		if ($this->request->getQuery('category')) {
			$parameters['category'] = $this->request->getQuery('category');
		}

		if ($this->request->getQuery('status')) {
			$parameters['status'] = $this->request->getQuery('status');
		}

		if (\count($parameters) === 0) {
			$this->redirect('this');
		} else {
			$this->redirect('this', $parameters);
		}
	}

	public function createComponentTeamListActionForm(): Form {
		$form = $this->formFactory->create();

		foreach ($this->template->teams as $team) {
			$form->addCheckbox('team_' . $team->id);
		}

		$submit = $form->addSubmit('send_message', 'messages.team.list.action.send_message.label');

		/** @var callable(Nette\Forms\Container): void */
		$listActionSubmitMessage = Closure::fromCallable([$this, 'listActionSubmitMessage']);
		$submit->onClick[] = $listActionSubmitMessage;

		return $form;
	}

	private function listActionSubmitMessage(Nette\Forms\Controls\SubmitButton $button): void {
		$values = $button->form->getValues();
		$selectedTeamIds = array_map(function($name) {
			return substr($name, \strlen('team_'));
		}, array_keys(array_filter((array) $values, function($value, $name) {
			return str_starts_with($name, 'team_') && \is_bool($value) && $value;
		}, \ARRAY_FILTER_USE_BOTH)));

		if (\count($selectedTeamIds) === 0) {
			$this->redirect('this');
		} else {
			$this->redirect('Communication:compose', ['ids' => implode(', ', $selectedTeamIds)]);
		}
	}

	private function personData(\stdClass $data): array {
		$fields = $this->context->parameters['entries']['fields']['person'];

		return $this->formatData($data, $fields);
	}

	private function teamData(\stdClass $data): array {
		$fields = $this->context->parameters['entries']['fields']['team'];

		return $this->formatData($data, $fields);
	}

	private function formatData(\stdClass $data, array $fields): array {
		$ret = [];
		foreach ($fields as $name => $field) {
			if (isset($field['label'][$this->locale])) {
				$label = $field['label'][$this->locale];
			} elseif ($field['type'] === 'country') {
				$label = $this->translator->translate('messages.team.person.country.label');
			} elseif ($field['type'] === 'phone') {
				$label = $this->translator->translate('messages.team.phone.label');
			} elseif ($field['type'] === 'sportident') {
				$label = $this->translator->translate('messages.team.person.si.label');
			} else {
				$label = $name . ':';
			}

			if (!$this->user->isInRole('admin') && (!isset($field['public']) || !$field['public'])) {
				continue;
			}

			if ($field['type'] === 'sportident') {
				$value = $data->$name->{SportidentControl::NAME_CARD_ID} ?? $this->translator->translate('messages.team.person.si.rent');
				$ret[] = $label . ' ' . $value;
				continue;
			} elseif ($field['type'] === 'country') {
				$country = isset($data->$name) ? $this->countries->getById($data->$name) : null;
				if (!$country) {
					$ret[] = $this->translator->translate('messages.team.data.country.unknown');
					continue;
				}
				$ret[] = $country->name;
				continue;
			} elseif ($field['type'] === 'enum' && isset($data->$name) && isset($field['options'][$data->$name]['label'][$this->locale])) {
				$ret[] = $label . ' ' . $field['options'][$data->$name]['label'][$this->locale];
				continue;
			} elseif ($field['type'] === 'checkboxlist' && isset($data->$name)) {
				$items = array_map(function(string $item) use ($field): string {
					return $field['items'][$item]['label'][$this->locale] ?? $item;
				}, $data->$name);
				$ret[] = $label . ' ' . implode(', ', $items);
				continue;
			}
			if (isset($data->$name)) {
				$ret[] = $label . ' ' . $data->$name;
			}
		}

		return $ret;
	}

	public static function serializeInvoiceItem(array $item): string {
		$parts = [$item['scope'] ?? '', $item['type'] ?? '', $item['key'] ?? '', $item['value'] ?? ''];

		return implode(':', $parts);
	}
}
