<?php

namespace App\Presenters;

use Nette;
use Nette\Utils\DateTime;
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
		parent::startup();
		if ($this->context->parameters['entries']['closing']->diff(new DateTime())->invert == 0) {
			throw new App\TooLateForAccessException;
		} elseif ($this->context->parameters['entries']['opening']->diff(new DateTime())->invert == 1) {
			throw new App\TooSoonForAccessException;
		}
	}


	public function renderList() {
		$where = array();
		$category = $this->context->httpRequest->getQuery('category');
		if ($category !== null) {
			$categories = $this->categories;
			if(isset($categories[$category])) {
				$where = $categories[$category];
			}
		}

		$duration = $this->context->httpRequest->getQuery('duration');
		if ($duration !== null && intVal($duration) > 0) {
			$where['duration'] = $duration;
		}

		if ($this->context->httpRequest->getQuery('status') !== null) {
			switch($this->context->httpRequest->getQuery('status')) {
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

		if (count($teams)) {
			$response = $this->context->getByType('Nette\Http\Response');
			$response->setContentType('text/csv', 'UTF-8');
			$fp = fOpen('php://output', 'a');
			$headers = array('#', 'name', 'registered', 'category');

			for ($i =1; $i <= $maxMembers; $i++) {
				$headers[] = 'm' . $i . 'lastname';
				$headers[] = 'm' . $i . 'firstname';
				$headers[] = 'm' . $i . 'gender';
				$headers[] = 'm' . $i . 'country';
				$headers[] = 'm' . $i . 'si';
				$headers[] = 'm' . $i . 'birth';
			}

			$headers[] = 'status';
			fPutCsv($fp, $headers);

			foreach ($teams as $team) {
				$row = array($team->id, $team->name, $team->timestamp, $this->categoryFormat($team->genderclass, $team->ageclass));
				$i = 0;
				$remaining = $maxMembers;
				foreach ($team->persons as $person) {
					$i++;
					$row[] = $person->lastname;
					$row[] = $person->firstname;
					$row[] = $person->gender;
					$row[] = $person->country->name;
					$row[] = $person->sportident;
					$row[] = $person->birth;
					$remaining--;
				}
				if ($remaining > 0) {
					for ($i = 0; $i < $remaining; $i++) {
						$row[] = '';
						$row[] = '';
						$row[] = '';
						$row[] = '';
						$row[] = '';
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

			$i = 0;
			foreach ($team->persons as $person) {
				$form['persons'][$i++]->setValues(array(
					'firstname' => $person->firstname,
					'lastname' => $person->lastname,
					'gender' => $person->gender,
					'country' => $person->country->id,
					'sportident' => $person->sportident,
					'needsportident' => $person->sportident === null ? true : false,
					'email' => $person->email,
					'birth' => $person->birth
				));
			}
			$form->setValues($default);
		}
		if(isset($this->parameters['id'])) {
			$form['save']->caption = 'messages.team.action.edit';
		}
		$form['save']->onClick[] = callback($this, 'processTeamForm');
		return $form;
	}


	public function processTeamForm(Nette\Forms\Controls\SubmitButton $button) {
		$form = $button->form;

		if ($this->action === 'edit') {
			$id = $this->parameters['id'];
			$team = $this->teams->getById($id);
			if (!$team) {
				$form->addError('messages.team.edit.error.404');
			} else if (!$this->user->isInRole('admin') && $team->status == 'paid') {
				$form->addError('messages.team.edit.error.already_paid');
			} elseif (!$this->user->isInRole('admin') && $this->user->identity->id != $id) {
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
			$sicount = 0;
			$team->name = $form['name']->value;
			$team->message = $form['message']->value;

			$team->genderclass = isset($form['genderclass']) ? $form['genderclass']->value : '';
			$team->ageclass = isset($form['ageclass']) ? $form['ageclass']->value : '';
			$team->duration = isset($form['duration']) ? $form['duration']->value : '';
			$this->teams->persistAndFlush($team);

			$members = [];
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
				$person->country = $this->countries->getById($member['country']);
				$person->sportident = ($member['needsportident'] ? null : $member['sportident']);
				$person->birth = $member['birth'];
				$person->email = $member['email'];
				$person->team = $team;

				if ($member['needsportident']) {
					$sicount++;
				}

				if (count($members) === 0) {
					$person->contact = true;
				}
				$members[] = $person;
			}

			$team->persons = $members;
			$this->teams->persistAndFlush($team);

			if($this->action === 'edit') {
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
				$mtemplate->cost = $this->cost(count($members), $sicount);
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
			if($this->action === 'edit') {
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

		$categories = array_keys($this->categories);
		$category = $form->addSelect('category', 'messages.team.list.filter.category.label', array_combine($categories, $categories))->setPrompt('messages.team.list.filter.category.all')->setAttribute('style', 'width:auto;');

		if ($this->context->httpRequest->getQuery('category')) {
			$category->setValue($this->context->httpRequest->getQuery('category'));
		}
		$category->controlPrototype->onchange('this.form.submit();');

		$durations = $this->context->parameters['entries']['categories']['duration'];
		if(count($durations) > 1) {
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

		foreach(array_keys($sexes) as $sex) {
			foreach (array_keys($ages) as $age) {
				$category = [];
				if($sex !== 'any') {
					$category['genderclass'] = $sex;
				}
				if($age !== 'any') {
					$category['ageclass'] = $age;
				}
				$categories[$sexes[$sex]['short'] . $ages[$age]['short']] = $category;
			}
		}

		return $categories;
	}
}
