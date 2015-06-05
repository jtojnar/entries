<?php

namespace App\Presenters;

use Nette;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use Tracy\Debugger;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use App;

class TeamPresenter extends BasePresenter {
	/** @var App\Model\CountryRepository @inject */
	public $countries;

	/** @var App\Model\TeamRepository @inject */
	public $teams;

	/** @var App\Model\PersonRepository @inject */
	public $persons;

	public function startup() {
		if (($this->action === 'register' || $this->action === 'edit') && !$this->user->isInRole('admin')) {
			if ($this->context->parameters['entries']['closing']->diff(new DateTime())->invert == 0) {
				throw new App\TooLateForAccessException;
			} else if ($this->context->parameters['entries']['opening']->diff(new DateTime())->invert == 1) {
				throw new App\TooSoonForAccessException;
			}
		}
		parent::startup();
	}

	public function renderList() {
		$where = array();
		$category = $this->context->httpRequest->getQuery('category');
		if ($category !== null) {
			$categories = $this->categories;
			if (isset($categories[$category])) {
				$where = $categories[$category];
			}
		}

		$duration = $this->context->httpRequest->getQuery('duration');
		if ($duration !== null && intVal($duration) > 0) {
			$where['duration'] = $duration;
		}

		if ($this->context->httpRequest->getQuery('status') !== null) {
			switch ($this->context->httpRequest->getQuery('status')) {
				case 'paid':
					$where['status'] = 'paid';
					break;
				case 'registered':
					$where['status'] = 'registered';
					break;
				default:
			}
		}

		if ($where === null) {
			$this->template->teams = $this->teams->findAll();
		} else {
			$this->template->teams = $this->teams->findBy($where);
		}

		$this->template->registerHelper('personData', callback($this, 'personData'));
		$this->template->registerHelper('teamData', callback($this, 'teamData'));

		$this->template->stats = array('count' => count($this->template->teams));
	}


	public function renderEdit($id) {
		if (!$this->user->isLoggedIn()) {
			$this->redirect('sign:in', array('return' => 'edit'));
		} else {
			if ($id === null) {
				$this->redirect('edit', array('id' => $this->user->identity->id));
			}
			if (!$this->user->isInRole('admin') && $this->user->identity->id != $id) {
				$backlink = $this->storeRequest('+ 48 hours');
				$this->redirect('sign:in', ['backlink' => $backlink]);
			}

			$team = $this->teams->getById($id);
			if (!$team) {
				$this->error($this->translator->translate('messages.team.edit.error.404'));
			}
			if (!$this->user->isInRole('admin') && $team->status == 'paid') {
				$this->flashMessage($this->translator->translate('messages.team.edit.error.already_paid'), 'error');
				$this->redirect('Homepage:');
			}
		}
	}


	public function actionConfirm($id) {
		$id = null;
		if (isset($this->parameters['id'])) {
			$id = $this->parameters['id'];
			if ($this->user->isInRole('admin')) {
				$team = $this->teams->getById($id);
				if ($team->status == 'registered') {
					$team->status = 'paid';
					$this->teams->persistAndFlush($team);
					$this->redirect('list');
				} else{
					$this->flashMessage($this->translator->translate('messages.team.edit.error.already_paid'), 'info');
					$this->redirect('Homepage:');
				}
			} else {

			}
		} else {

		}
	}

	public function actionExport() {
		if (!$this->user->isInRole('admin')) {
			$backlink = $this->storeRequest('+ 48 hours');
			$this->redirect('sign:in', ['backlink' => $backlink]);
		}

		$teams = $this->teams->findAll();
		$maxMembers = $this->context->parameters['entries']['maxMembers'];

		$teamFields = $this->presenter->context->parameters['entries']['fields']['team'];
		$personFields = $this->presenter->context->parameters['entries']['fields']['person'];

		if (count($teams)) {
			$response = $this->context->getByType('Nette\Http\Response');
			$response->setContentType('text/csv', 'UTF-8');
			$fp = fOpen('php://output', 'a');
			$headers = array('#', 'name', 'registered', 'category');

			foreach ($teamFields as $name => $field) {
				$headers[] = $name;
			}

			for ($i =1; $i <= $maxMembers; $i++) {
				$headers[] = 'm' . $i . 'lastname';
				$headers[] = 'm' . $i . 'firstname';
				$headers[] = 'm' . $i . 'gender';
				foreach ($personFields as $name => $field) {
					$headers[] = 'm' . $i . $name;
				}
				$headers[] = 'm' . $i . 'birth';
			}

			$headers[] = 'status';
			fPutCsv($fp, $headers);

			foreach ($teams as $team) {
				$row = array($team->id, $team->name, $team->timestamp, $this->categoryFormat($team));
				foreach ($teamFields as $name => $field) {
					$f = isset($team->getJsonData()->$name) ? $team->getJsonData()->$name : null;
					if ($f) {
						if ($field['type'] === 'country') {
							$row[] = $this->countries->getById($f)->name;
						} else {
							$row[] = $f;
						}
					} else {
						$row[] = '';
					}
				}

				$i = 0;
				$remaining = $maxMembers;
				foreach ($team->persons as $person) {
					$i++;
					$row[] = $person->lastname;
					$row[] = $person->firstname;
					$row[] = $person->gender;
					foreach ($personFields as $name => $field) {
						$f = isset($person->getJsonData()->$name) ? $person->getJsonData()->$name : null;
						if ($f) {
							if ($field['type'] === 'country') {
								$row[] = $this->countries->getById($f)->name;
							} else {
								$row[] = $f;
							}
						} else {
							$row[] = '';
						}
					}
					$row[] = $person->birth;
					$remaining--;
				}
				if ($remaining > 0) {
					for ($i = 0; $i < $remaining; $i++) {
						$row[] = '';
						$row[] = '';
						$row[] = '';
						foreach ($personFields as $name => $field) {
							$row[] = '';
						}
						$row[] = '';
					}
				}
				$row[] = $team->status;
				fPutCsv($fp, $row);
			}
			fClose($fp);
			exit;
		} else {
			$this->flashMessage('messages.team.list.empty', 'error');
			$this->redirect('list');
		}
	}


	protected function createComponentTeamForm($name) {
		$form = new App\Components\TeamForm($this->countries->fetchIdNamePairs(), $this, $name);
		if (isset($this->parameters['id']) && !$form->submitted) {
			$id = $this->parameters['id'];
			$team = $this->teams->getById($id);
			$default = array();
			$default['name'] = $team->name;
			$default['ageclass'] = $team->ageclass;
			$default['genderclass'] = $team->genderclass;
			$default['message'] = $team->message;

			$fields = $this->presenter->context->parameters['entries']['fields']['team'];
			foreach ($fields as $name => $field) {
				if (isset($team->getJsonData()->$name)) {
					$default[$name] = $team->getJsonData()->$name;
				} else if ($field['type'] === 'sportident') {
					$default[$name . 'Needed'] = true;
				}
			}

			$i = 0;
			$fields = $this->presenter->context->parameters['entries']['fields']['person'];
			foreach ($team->persons as $person) {
				$personDefault = array(
					'firstname' => $person->firstname,
					'lastname' => $person->lastname,
					'gender' => $person->gender,
					'email' => $person->email,
					'birth' => $person->birth
				);

				foreach ($fields as $name => $field) {
					if (isset($person->getJsonData()->$name)) {
						$personDefault[$name] = $person->getJsonData()->$name;
					} else if ($field['type'] === 'sportident') {
						$default[$name . 'Needed'] = true;
					}
				}

				$form['persons'][$i++]->setValues($personDefault);
			}
			$form->setValues($default);
		}
		if (isset($this->parameters['id'])) {
			$form['save']->caption = 'messages.team.action.edit';
		}
		$form['save']->onClick[] = callback($this, 'processTeamForm');
		return $form;
	}


	public function processTeamForm(Nette\Forms\Controls\SubmitButton $button) {
		if (!$this->user->isInRole('admin')) {
			if ($this->context->parameters['entries']['closing']->diff(new DateTime())->invert == 0) {
				throw new App\TooLateForAccessException;
			} else if ($this->context->parameters['entries']['opening']->diff(new DateTime())->invert == 1) {
				throw new App\TooSoonForAccessException;
			}
		}

		$form = $button->form;

		if ($this->action === 'edit') {
			$id = $this->parameters['id'];
			$team = $this->teams->getById($id);
			if (!$team) {
				$form->addError('messages.team.edit.error.404');
			} else if (!$this->user->isInRole('admin') && $team->status == 'paid') {
				$form->addError('messages.team.edit.error.already_paid');
			} else if (!$this->user->isInRole('admin') && $this->user->identity->id != $id) {
				$backlink = $this->storeRequest('+ 48 hours');
				$this->redirect('sign:in', ['backlink' => $backlink]);
			}
		} else {
			$team = new App\Model\Team;
			$password = Nette\Utils\Strings::random();
			$team->password = Nette\Security\Passwords::hash($password);
			$team->ip = $this->context->httpRequest->remoteAddress;
		}

		try {
			$invoice = new App\Model\Invoice;
			$team->name = $form['name']->value;
			$team->message = $form['message']->value;

			$team->genderclass = isset($form['genderclass']) ? $form['genderclass']->value : '';
			$team->ageclass = isset($form['ageclass']) ? $form['ageclass']->value : '';
			$team->duration = isset($form['duration']) ? $form['duration']->value : '';

			$fields = $this->presenter->context->parameters['entries']['fields']['team'];
			$jsonData = [];
			foreach ($fields as $name => $field) {
				if ($field['type'] === 'sportident' && $form[$name . 'Needed']->value) {
					$jsonData[$name] = null;
				} else {
					$jsonData[$name] = $form[$name]->value;
				}

				if (isset($field['fee']) && $jsonData[$name] === null) {
					$invoice->addItem($name, $field['fee']);
				}

				if ($field['type'] === 'enum' && isset($field['options'][$form[$name]->value]) && isset($field['options'][$form[$name]->value]['fee']) && $jsonData[$name]) {
					$invoice->addItem($name . '-' . $form[$name]->value, $field['options'][$form[$name]->value]['fee']);
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

			$personFee = $this->context->parameters['entries']['fees']['person'];
			if (isset($this->context->parameters['entries']['categories']['age']) && isset($this->context->parameters['entries']['categories']['age'][$team->ageclass]) && isset($this->context->parameters['entries']['categories']['age'][$team->ageclass]['fee'])) {
				$personFee = $this->context->parameters['entries']['categories']['age'][$team->ageclass]['fee'];
			}
			$invoice->createItem('person', $personFee);

			$fields = $this->presenter->context->parameters['entries']['fields']['person'];
			foreach ($form['persons']->values as $member) {
				$firstname = $member['firstname'];
				if (!isset($address)) {
					$address = $member['email'];
				}
				if (!isset($name)) {
					$name = $member['firstname'].' '.$member['lastname'];
				}
				$person = new App\Model\Person;

				$person->firstname = $firstname;
				$person->lastname = $member['lastname'];
				$person->gender = $member['gender'];
				$person->birth = $member['birth'];
				$person->email = $member['email'];
				$person->team = $team;

				$jsonData = [];
				foreach ($fields as $name => $field) {
					if ($field['type'] === 'sportident' && $member[$name . 'Needed']) {
						$jsonData[$name] = null;
					} else {
						$jsonData[$name] = $member[$name];
					}
					if (isset($field['fee']) && $jsonData[$name] === null) {
						$invoice->addItem($name, $field['fee']);
					}

					if ($field['type'] === 'enum' && isset($field['options'][$member[$name]]) && isset($field['options'][$member[$name]]['fee']) && $jsonData[$name]) {
						$invoice->addItem($name . '-' . $member[$name], $field['options'][$member[$name]]['fee']);
					}
				}

				if (count($team->persons) === 0) {
					$person->contact = true;
				}
				$person->setJsonData($jsonData);

				$invoice->addItem('person');
				$this->persons->persist($person);
			}

			$this->persons->flush();
			$this->teams->persistAndFlush($team);

			if ($this->action === 'edit') {
				$this->flashMessage($this->translator->translate('messages.team.success.edit'));
			} else {
				$mtemplate = $this->createTemplate();
				$mtemplate->registerHelper('categoryFormat', callback($this, 'categoryFormat'));

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
				$mtemplate->cost = $invoice->getTotal();
				$mtemplate->organiserMail = $this->context->parameters['webmasterEmail'];
				$mail = new Message;
				$mail->setFrom($mtemplate->organiserMail)->addTo($address)->setHtmlBody($mtemplate);

				$mailer = new SendmailMailer;
				$mailer->send($mail);

				$this->flashMessage($this->translator->translate('messages.team.success.add', null, array('password' => $password)));
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


	public function createComponentTeamListFilterForm() {
		$form = new Form;
		$renderer = $form->renderer;
		$form->setTranslator($this->translator);
		$form->setMethod("GET");
		$renderer = new \Nextras\Forms\Rendering\Bs3FormRenderer;
		$form->setRenderer($renderer);
		$form->elementPrototype->removeClass('form-horizontal')->addClass('form-inline');
		$renderer->wrappers['controls']['container'] = 'p';
		$renderer->wrappers['pair']['container'] = null;
		$renderer->wrappers['label']['container'] = null;
		$renderer->wrappers['control']['container'] = null;
		$renderer->wrappers['control']['errors'] = false;
		$renderer->wrappers['form']['errors'] = false;
		$renderer->wrappers['hidden']['container'] = null;

		if (isset($this->presenter->context->parameters['entries']['categories']['custom'])) {
			$categories = callback($this->presenter->context->parameters['entries']['categories']['custom'], 'getCategories')->invoke();
		} else {
			$categories = array_keys($this->categories);
		}

		$category = $form->addSelect('category', 'messages.team.list.filter.category.label', array_combine($categories, $categories))->setPrompt('messages.team.list.filter.category.all')->setAttribute('style', 'width:auto;');

		if ($this->context->httpRequest->getQuery('category')) {
			$category->setValue($this->context->httpRequest->getQuery('category'));
		}
		$category->controlPrototype->onchange('this.form.submit();');

		$durations = $this->context->parameters['entries']['categories']['duration'];
		if (count($durations) > 1) {
			$duration = $form->addSelect('duration', null, array_combine($durations, $durations))->setPrompt('messages.team.list.filter.duration.all')->setAttribute('style', 'width:auto;');
			if ($this->context->httpRequest->getQuery('duration')) {
				$duration->setValue($this->context->httpRequest->getQuery('duration'));
			}
			$duration->controlPrototype->onchange('this.form.submit();');
		}

		if ($this->user->isInRole('admin')) {
			$status = $form->addSelect('status', 'messages.team.list.filter.status.label', array('registered' => 'messages.team.list.filter.status.registered', 'paid' => 'messages.team.list.filter.status.paid'))->setPrompt('messages.team.list.filter.status.all')->setAttribute('style', 'width:auto;');
			if ($this->context->httpRequest->getQuery('status')) {
				$status->setValue($this->context->httpRequest->getQuery('status'));
			}
			$status->controlPrototype->onchange('this.form.submit();');
		}

		$submit = $form->addSubmit('filter', 'messages.team.list.filter.submit.label');
		$submit->controlPrototype->onload("this.setAttribute('style', 'display: none');");
		$form->onValidate[] = callback($this, 'filterRedir');
		return $form;
	}


	public function filterRedir(Nette\Forms\Form $form) {
		$parameters = array();

		$durations = $this->context->parameters['entries']['categories']['duration'];
		if ($this->context->httpRequest->getQuery('category')) {
			$parameters['category'] = $this->context->httpRequest->getQuery('category');
		}

		if (in_array($this->context->httpRequest->getQuery('duration'), $durations)) {
			$parameters['duration'] = $this->context->httpRequest->getQuery('duration');
		}

		if ($this->context->httpRequest->getQuery('status')) {
			$parameters['status'] = $this->context->httpRequest->getQuery('status');
		}

		if (count($parameters) == 0) {
			$this->redirect('this');
		} else {
			$this->redirect('this', $parameters);
		}
	}

	public function getCategories() {
		$sexes = $this->presenter->context->parameters['entries']['categories']['gender'];
		$ages = $this->context->parameters['entries']['categories']['age'];
		$categories = array();

		foreach (array_keys($sexes) as $sex) {
			foreach (array_keys($ages) as $age) {
				$category = [];
				if ($sex !== 'any') {
					$category['genderclass'] = $sex;
				}
				if ($age !== 'any') {
					$category['ageclass'] = $age;
				}
				$categories[$sexes[$sex]['short'] . $ages[$age]['short']] = $category;
			}
		}

		return $categories;
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
			} else if ($field['type'] === 'country') {
				$label = $this->translator->translate('messages.team.person.country.label');
			} else if ($field['type'] === 'phone') {
				$label = $this->translator->translate('messages.team.phone.label');
			} else if ($field['type'] === 'sportident') {
				$label = $this->translator->translate('messages.team.person.si.label');
			} else {
				$label = $name . ':';
			}

			if(!$this->user->isInRole('admin') && isset($field['private']) && $field['private']) {
				continue;
			}

			if ($field['type'] === 'sportident') {
				if (!isset($data->$name) || $data->$name === null) {
					$ret[] = $label . ' ' . $this->translator->translate('messages.team.person.si.rent');
					continue;
				}
			} else if ($field['type'] === 'country') {
				$country = isset($data->$name) ? $this->countries->getById($data->$name) : null;
				if (!$country) {
					$ret[] = $this->translator->translate('messages.team.data.country.unknown');
					continue;
				}
				$ret[] = (string) Html::el('span', ['class' => 'flag flag-'. $country->code]) . ' ' . $country->name;
				continue;
			}
			if (isset($data->$name)) {
				$ret[] = $label . ' ' . $data->$name;
			}
		}

		return $ret;
	}
}
