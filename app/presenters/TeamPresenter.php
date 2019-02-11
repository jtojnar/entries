<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Components\SportidentControl;
use App\Exporters;
use App\Model\Invoice;
use Exception;
use Nette;
use Nette\Application\UI\Form;
use Nette\Mail\Message;
use Nette\Utils\Callback;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use Nextras\Forms\Rendering\Bs4FormRenderer;
use Nextras\Forms\Rendering\FormLayout;
use Tracy\Debugger;

class TeamPresenter extends BasePresenter {
	/** @var App\Model\CountryRepository @inject */
	public $countries;

	/** @var App\Model\TeamRepository @inject */
	public $teams;

	/** @var App\Model\PersonRepository @inject */
	public $persons;

	/** @var App\Model\InvoiceRepository @inject */
	public $invoices;

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

	public function renderList(): void {
		$where = [];
		$category = $this->context->getByType('Nette\Http\Request')->getQuery('category');
		if ($category !== null) {
			$where = ['category' => explode('|', $category)];
		}

		if ($this->context->getByType('Nette\Http\Request')->getQuery('status') !== null) {
			switch ($this->context->getByType('Nette\Http\Request')->getQuery('status')) {
				case 'paid':
					$where['status'] = 'paid';
					break;
				case 'registered':
					$where['status'] = 'registered';
					break;
				default:
			}
		}

		/** @var Nette\Bridges\ApplicationLatte\Template $template */
		$template = $this->template;

		$template->teams = $this->teams->findBy($where);

		$template->getLatte()->addFilter('personData', Callback::closure($this, 'personData'));
		$template->getLatte()->addFilter('teamData', Callback::closure($this, 'teamData'));

		$template->stats = ['count' => \count($template->teams)];
	}

	public function renderEdit(int $id = null): void {
		if (!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['return' => 'edit']);
		} else {
			/** @var Nette\Security\Identity $identity */
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

	public function actionExport($type = 'csv'): void {
		if (!$this->user->isInRole('admin')) {
			$backlink = $this->storeRequest('+ 48 hours');
			$this->redirect('Sign:in', ['backlink' => $backlink]);
		}

		$where = [];

		$category = $this->context->getByType('Nette\Http\Request')->getQuery('category');
		if ($category !== null) {
			$where = ['category' => explode('|', $category)];
		}

		switch ($this->context->getByType('Nette\Http\Request')->getQuery('status')) {
			case 'paid':
				$where['status'] = 'paid';
				break;
			case 'registered':
				$where['status'] = 'registered';
				break;
			default:
		}

		$teams = $this->teams->findBy($where);
		$maxMembers = $this->context->parameters['entries']['maxMembers'];

		$teamFields = $this->presenter->context->parameters['entries']['fields']['team'];
		$personFields = $this->presenter->context->parameters['entries']['fields']['person'];

		if (\count($teams)) {
			if ($type === 'meos') {
				$exporter = new Exporters\MeosExporter($teams, Callback::closure($this, 'categoryFormat'));
				$response = $this->context->getByType('Nette\Http\Response');
				$response->setContentType($exporter->getMimeType(), 'UTF-8');
				$exporter->output();
			} else {
				$exporter = new Exporters\CsvExporter($teams, $this->countries, $teamFields, $personFields, Callback::closure($this, 'categoryFormat'), $maxMembers);
				$response = $this->context->getByType('Nette\Http\Response');
				$response->setContentType('text/plain', 'UTF-8');
				$exporter->output();
			}
		} else {
			$this->flashMessage('messages.team.list.empty', 'error');
			$this->redirect('list');
		}
	}

	protected function createComponentTeamForm(string $name) {
		$form = new App\Components\TeamForm($this->countries->fetchIdNamePairs(), $this->categories, $this->context->parameters['entries'], $this->locale, $this->translator, $this, $name);
		if ($this->getParameter('id') && !$form->isSubmitted()) {
			$id = (int) $this->getParameter('id');
			$team = $this->teams->getById($id);
			$default = [];
			$default['name'] = $team->name;
			$default['category'] = $team->category;
			$default['message'] = $team->message;
			$default['persons'] = [];

			$fields = $this->presenter->context->parameters['entries']['fields']['team'];
			foreach ($fields as $name => $field) {
				if (isset($team->getJsonData()->$name)) {
					$default[$name] = $team->getJsonData()->$name;
				} elseif ($field['type'] === 'sportident') {
					$default[$name] = [
						SportidentControl::NAME_NEEDED => true,
					];
				}
			}

			$fields = $this->presenter->context->parameters['entries']['fields']['person'];
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
		if ($this->getParameter('id')) {
			$form['save']->caption = 'messages.team.action.edit';
		}
		$form['save']->onClick[] = Callback::closure($this, 'processTeamForm');

		return $form;
	}

	public function processTeamForm(Nette\Forms\Controls\SubmitButton $button): void {
		if (!$this->user->isInRole('admin')) {
			if ($this->context->parameters['entries']['closing']->diff(new DateTime())->invert === 0) {
				throw new App\TooLateForAccessException();
			} elseif ($this->context->parameters['entries']['opening']->diff(new DateTime())->invert === 1) {
				throw new App\TooSoonForAccessException();
			}
		}

		/** @var App\Components\TeamForm $form */
		$form = $button->form;
		/** @var string $password */
		$password = null;

		$this->cleanNonApplicableFields($form);

		if ($this->action === 'edit') {
			$id = (int) $this->getParameter('id');
			$team = $this->teams->getById($id);
			/** @var Nette\Security\Identity $identity */
			$identity = $this->user->identity;

			if (!$team) {
				$form->addError('messages.team.edit.error.404');
			} elseif (!$this->user->isInRole('admin') && $team->status === 'paid') {
				$form->addError('messages.team.edit.error.already_paid');
			} elseif (!$this->user->isInRole('admin') && $identity->id !== $id) {
				$backlink = $this->storeRequest('+ 48 hours');
				$this->redirect('Sign:in', ['backlink' => $backlink]);
			}
		} else {
			$team = new App\Model\Team();
			$password = Nette\Utils\Random::generate();
			$team->password = Nette\Security\Passwords::hash($password);
			$team->ip = $this->context->getByType('Nette\Http\Request')->remoteAddress;
		}

		try {
			$invoice = new Invoice();
			$invoice->status = Invoice::STATUS_NEW;
			$invoice->team = $team;
			$invoice->items = [];

			$team->name = $form['name']->value;
			$team->message = $form['message']->value;

			$team->category = isset($form['category']) ? $form['category']->value : '';

			$fields = $this->presenter->context->parameters['entries']['fields']['team'];
			$jsonData = [];
			foreach ($fields as $name => $field) {
				$jsonData[$name] = $form[$name]->value;
				$type = $field['type'];

				if ($type === 'sportident' && isset($field['fee']) && (($jsonData[$name] ?? [])[SportidentControl::NAME_NEEDED] ?? null) === true) {
					$invoice->addItem(self::serializeInvoiceItem([
						'type' => $type,
						'scope' => 'team',
						'key' => $name,
					]), $field['fee']);
				} elseif ($type === 'checkbox' && isset($field['fee']) && $jsonData[$name]) {
					$invoice->addItem(self::serializeInvoiceItem([
						'type' => $type,
						'scope' => 'team',
						'key' => $name,
					]), $field['fee']);
				} elseif ($type === 'enum' && isset($field['options'][$form[$name]->value]) && isset($field['options'][$form[$name]->value]['fee']) && $jsonData[$name]) {
					$invoice->addItem(self::serializeInvoiceItem([
						'type' => $type,
						'scope' => 'team',
						'key' => $name,
						'value' => $form[$name]->value,
					]), $field['options'][$form[$name]->value]['fee']);
				} elseif ($type === 'checkboxlist') {
					foreach ($jsonData[$name] as $item) {
						if (isset($field['items'][$item]['fee'])) {
							$invoice->addItem(self::serializeInvoiceItem([
								'type' => $type,
								'scope' => 'team',
								'key' => $name,
								'value' => $item,
							]), $field['items'][$item]['fee']);
						}
					}
				}
			}
			$team->setJsonData($jsonData);

			if ($this->action === 'edit') {
				foreach ($team->persons as $person) {
					$this->persons->remove($person);
				}
				$this->persons->flush();
			}

			$this->teams->persistAndFlush($team);

			$personFee = $this->categories->getCategoryData()[$team->category]['fee'];
			$invoice->createItem(self::serializeInvoiceItem([
				'type' => '~entry',
				'scope' => 'person',
			]), $personFee);

			$fields = $this->presenter->context->parameters['entries']['fields']['person'];

			/** @var ?string $address */
			$address = null;
			/** @var string $name */
			$name = null;

			foreach ($form['persons']->values as $member) {
				$firstname = $member['firstname'];
				if ($address === null) {
					$address = $member['email'];
				}
				if (!isset($name)) {
					$name = $member['firstname'] . ' ' . $member['lastname'];
				}
				$person = new App\Model\Person();

				$person->firstname = $firstname;
				$person->lastname = $member['lastname'];
				$person->gender = $member['gender'];
				$person->birth = $member['birth'];
				$person->email = $member['email'];
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
						]), $field['fee']);
					} elseif ($type === 'checkbox' && isset($field['fee']) && $jsonData[$name]) {
						$invoice->addItem(self::serializeInvoiceItem([
							'type' => $type,
							'scope' => 'person',
							'key' => $name,
						]), $field['fee']);
					} elseif ($type === 'enum' && isset($field['options'][$member[$name]]) && isset($field['options'][$member[$name]]['fee']) && $jsonData[$name]) {
						$invoice->addItem(self::serializeInvoiceItem([
							'type' => $type,
							'scope' => 'person',
							'key' => $name,
							'value' => $member[$name],
						]), $field['options'][$member[$name]]['fee']);
					} elseif ($type === 'checkboxlist') {
						foreach ($jsonData[$name] as $item) {
							if (isset($field['items'][$item]['fee'])) {
								$invoice->addItem(self::serializeInvoiceItem([
									'type' => $type,
									'scope' => 'person',
									'key' => $name,
									'value' => $item,
								]), $field['items'][$item]['fee']);
							}
						}
					}
				}

				if (\count($team->persons) === 0) {
					$person->contact = true;
				}
				$person->setJsonData($jsonData);

				$invoice->addItem(self::serializeInvoiceItem([
					'type' => '~entry',
					'scope' => 'person',
				]));
				$this->persons->persist($person);
			}

			if (isset($this->presenter->context->parameters['entries']['invoiceModifier'])) {
				$invoiceModifier = Callback::closure($this->presenter->context->parameters['entries']['invoiceModifier'], 'modify');
				$invoiceModifier($team, $invoice, $this->context->parameters['entries']);
			}

			$this->persons->flush();
			$this->teams->persistAndFlush($team);

			foreach ($team->invoices as $inv) {
				if ($inv->status === Invoice::STATUS_NEW) {
					$inv->status = Invoice::STATUS_CANCELLED;
					$this->invoices->persist($inv);
				}
			}

			$this->invoices->persist($invoice);

			$this->invoices->flush();

			if ($this->action === 'edit') {
				$this->flashMessage($this->translator->translate('messages.team.success.edit'));
			} else {
				/** @var Nette\Bridges\ApplicationLatte\Template $mtemplate */
				$mtemplate = $this->createTemplate();
				$mtemplate->getLatte()->addFilter('categoryFormat', Callback::closure($this, 'categoryFormat'));

				$appDir = $this->context->parameters['appDir'];
				if (file_exists($appDir . '/templates/Mail/verification.' . $this->locale . '.latte')) {
					$mtemplate->setFile($appDir . '/templates/Mail/verification.' . $this->locale . '.latte');
				} else {
					$mtemplate->setFile($appDir . '/templates/Mail/verification.latte');
				}

				$mtemplate->team = $team;
				$mtemplate->people = $team->persons;
				$mtemplate->id = $team->id;
				$mtemplate->name = $name;
				$mtemplate->password = $password;
				$mtemplate->invoice = $invoice;
				$mtemplate->organiserMail = $this->context->parameters['webmasterEmail'];
				$emogrifier = new \Pelago\Emogrifier();
				$emogrifier->setHtml($mtemplate);
				$emogrifier->setCss(file_get_contents($appDir . '/templates/Mail/style.css'));
				$emogrifier->enableCssToHtmlMapping();

				$mail = new Message();
				$mail->setFrom($mtemplate->organiserMail)->addTo($address)->setHtmlBody($emogrifier->emogrify());

				$mailer = $this->context->getByType('Nette\Mail\IMailer');
				$mailer->send($mail);

				$this->flashMessage($this->translator->translate('messages.team.success.add', null, ['password' => $password]));
			}
			$this->redirect('Homepage:');
		} catch (Exception $e) {
			if ($e instanceof Nette\Application\AbortException) {
				throw $e;
			}
			Debugger::log($e);
			if ($this->action === 'edit') {
				$form->addError('messages.team.error.edit_general');
			} else {
				$form->addError('messages.team.error.add_general');
			}
		}
	}

	public function cleanNonApplicableFields(Nette\Forms\Form $form): void {
		$category = $form['category']->getValue();

		$teamFields = $this->presenter->context->parameters['entries']['fields']['team'];
		foreach ($teamFields as $name => $field) {
			if (isset($field['applicableCategories']) && !\in_array($category, $field['applicableCategories'], true)) {
				$form[$name]->setValue(null);
			}
		}

		$personFields = $this->presenter->context->parameters['entries']['fields']['person'];
		foreach ($form['persons']->values as $member) {
			foreach ($personFields as $name => $field) {
				if (isset($field['applicableCategories']) && !\in_array($category, $field['applicableCategories'], true)) {
					$form['persons'][$name]->setValue(null);
				}
			}
		}
	}

	public function createComponentTeamListFilterForm() {
		$form = new Form();
		$form->setRenderer(new Bs4FormRenderer(FormLayout::INLINE));
		$form->setTranslator($this->translator);
		$form->setMethod('GET');

		$category = $form['category'] = new App\Components\CategoryEntry('messages.team.list.filter.category.label', $this->categories, true);
		$category->setPrompt('messages.team.list.filter.category.all');
		$category->setAttribute('style', 'width:auto;');

		if ($this->context->getByType('Nette\Http\Request')->getQuery('category')) {
			$category->setValue($this->context->getByType('Nette\Http\Request')->getQuery('category'));
		}
		$category->controlPrototype->onchange('this.form.submit();');

		if ($this->user->isInRole('admin')) {
			$status = $form->addSelect('status', 'messages.team.list.filter.status.label', ['registered' => 'messages.team.list.filter.status.registered', 'paid' => 'messages.team.list.filter.status.paid'])->setPrompt('messages.team.list.filter.status.all')->setAttribute('style', 'width:auto;');
			if ($this->context->getByType('Nette\Http\Request')->getQuery('status')) {
				$status->setValue($this->context->getByType('Nette\Http\Request')->getQuery('status'));
			}
			$status->controlPrototype->onchange('this.form.submit();');
		}

		$submit = $form->addSubmit('filter', 'messages.team.list.filter.submit.label');
		$submit->controlPrototype->onload("this.setAttribute('style', 'display: none');");
		$form->onValidate[] = Callback::closure($this, 'filterRedir');

		return $form;
	}

	public function filterRedir(Nette\Forms\Form $form): void {
		$parameters = [];

		if ($this->context->getByType('Nette\Http\Request')->getQuery('category')) {
			$parameters['category'] = $this->context->getByType('Nette\Http\Request')->getQuery('category');
		}

		if ($this->context->getByType('Nette\Http\Request')->getQuery('status')) {
			$parameters['status'] = $this->context->getByType('Nette\Http\Request')->getQuery('status');
		}

		if (\count($parameters) === 0) {
			$this->redirect('this');
		} else {
			$this->redirect('this', $parameters);
		}
	}

	public function personData($data) {
		$fields = $this->presenter->context->parameters['entries']['fields']['person'];

		return $this->formatData($data, $fields);
	}

	public function teamData($data) {
		$fields = $this->presenter->context->parameters['entries']['fields']['team'];

		return $this->formatData($data, $fields);
	}

	public function formatData($data, $fields) {
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

			if (!$this->user->isInRole('admin') && isset($field['private']) && $field['private']) {
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
				$ret[] = (string) Html::el('span', ['class' => 'flag-icon flag-icon-' . $country->code]) . ' ' . $country->name;
				continue;
			} elseif ($field['type'] === 'enum' && isset($data->$name) && isset($field['options'][$data->$name]['label'][$this->locale])) {
				$ret[] = $label . ' ' . $field['options'][$data->$name]['label'][$this->locale];
				continue;
			} elseif ($field['type'] === 'checkboxlist' && isset($data->$name)) {
				$items = array_map(function($item) use ($field) {
					return $field['items'][$item]['label'][$this->locale];
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

	public static function serializeInvoiceItem($item) {
		$parts = [$item['scope'] ?? '', $item['type'] ?? '', $item['key'] ?? '', $item['value'] ?? ''];

		return implode(':', $parts);
	}
}
