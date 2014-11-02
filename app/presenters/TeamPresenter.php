<?php

namespace App\Presenters;

use Nette;
use Nette\DateTime;
use Tracy\Debugger;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use App;

class TeamPresenter extends BasePresenter {
	/** @var App\Model\CountryModel @inject */
	public $countries;
	/** @var App\Model\TeamModel @inject */
	public $teams;

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
			$categories = $this->getCategories();
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
			$this->template->teams = $this->teams->getTeams();
		} else {
			$this->template->teams = $this->teams->getTeams()->where($where);
		}

		$this->template->countries = $this->countries->getCountries();
		$this->template->stats = array('count' => count($this->template->teams));
	}


	public function renderEdit($id) {
		if (!$this->user->isLoggedIn()) {
			$this->redirect('sign:in', array('return' => 'edit'));
		} else {
			if ($id == null) {
				$this->redirect('edit', array('id'=>$this->user->identity->id));
			}
			if (!$this->user->isInRole('admin') && $this->user->identity->id != $id) {
				$backlink = $this->getApplication()->storeRequest('+ 48 hours');
				$this->redirect('sign:in', $backlink);
			}
			if (!$this->user->isInRole('admin') && $this->teams->getStatus($id) == 'paid') {
				$this->flashMessage($this->translator->translate('messages.team.edit.error.already_paid'), 'error');
				$this->redirect('Homepage:');
			}
			if ($this->teams->getTeam($id) == null) {
				$this->error($this->translator->translate('This team does not exist.'));
			}
		}
	}


	public function actionConfirm($id) {
		$id = null;
		if (isset($this->parameters['id'])) {
			$id = $this->parameters['id'];
			if ($this->user->isInRole('admin')) {
				if ($this->teams->getStatus($id) == 'registered') {
					$this->teams->updateStatus($id, 'paid');
					$this->redirect('list');
				} else{
					$this->flashMessage($this->translator->translate('This team has already paid entry fee.'), 'info');
					$this->redirect('Homepage:');
				}
			} else {

			}
		} else {

		}
	}

	public function actionExport() {
		if (!$this->user->isInRole('admin')) {
			$backlink = $this->getApplication()->storeRequest('+ 48 hours');
			$this->redirect('sign:in', $backlink);
		}

		$teams = $this->teams->getTeams();
		$countries = $this->countries->getCountries();
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
				foreach ($team->related(BaseModel::TEAM_PERSON) as $person) {
					$i++;
					$row[] = $person->lastname;
					$row[] = $person->firstname;
					$row[] = $person->gender;
					$row[] = $countries[$person->country_id]->name;
					$row[] = $person->sportident;
					$row[] = $person->birth;
					$remaining = $maxMembers-$i;
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
		$form = new App\Components\TeamForm($this->countries, $this, $name);
		if (isset($this->parameters['id']) && !$form->submitted) {
			$id = $this->parameters['id'];
			$team = $this->teams->getTeam($id);
			$default = array();
			$default['name'] = $team->name;
			$default['ageclass'] = $team->ageclass;
			$default['genderclass'] = $team->genderclass;
			$default['message'] = $team->message;

			$persons = $this->teams->getPersons($team->id);
			$i = 0;
			foreach ($persons as $person) {
				$form['persons'][$i++]->setValues(array(
					'firstname' => $person->firstname,
					'lastname' => $person->lastname,
					'gender' => $person->gender,
					'country' => $person->country_id,
					'sportident' => $person->sportident,
					'needsportident' => $person->sportident === null ? true : false,
					'email' => $person->email,
					'birth' => $person->birth->format('Y-m-d')
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
		$form = $button->getForm();
		if (isset($this->parameters['id'])) {
			$id = $this->parameters['id'];
			if (!$this->user->isInRole('admin') && $this->teams->getStatus($id) == 'paid') {
				$form->addError('messages.team.edit.error.already_paid');
			} elseif (!$this->user->isInRole('admin') && $this->user->identity->id != $id) {
				$backlink = $this->getApplication()->storeRequest('+ 48 hours');
				$this->redirect('sign:in', $backlink);
			} else {
				$this->teams->beginTransaction();
				$template = $this->template;
				$i = 0;
				try {
					$teamdata = array('name' => $form['name']->getValue(), 'message' => $form['message']->getValue());
					if(isset($form['genderclass'])) {
						$teamdata['genderclass'] = $form['genderclass']->getValue();
					}
					if(isset($form['ageclass'])) {
						$teamdata['ageclass'] = $form['ageclass']->getValue();
					}
					if(isset($form['duration'])) {
						$teamdata['duration'] = $form['duration']->getValue();
					}
					$ids = $this->teams->getPersonsIds($id);
					$members = array();
					$newmembers = array();
					foreach ($form['persons']->values as $person) {
						$firstname = $person['firstname'];
						if (!empty($firstname)) {
							if (!isset($address)) {
								$address = $person['email'];
							}
							if (!isset($name)) {
								$name = $person['firstname'].' '.$person['lastname'];
							}
							$person = array('firstname' => $firstname, 'lastname' => $person['lastname'], 'gender' => $person['gender'], 'country_id' => $person['country'], 'sportident' => ($person['needsportident'] ? null : $person['sportident']), 'birth' => $person['birth'], 'email' => $person['email']);
						$person['contact'] = chr(0);
						if ($i == 0) {
							$person['contact'] = chr(1);
						}
							if (isset($ids[$i]) && $this->teams->personExists($ids[$i])) {
								$members[$ids[$i]] = $person;
							} else {
								$person['team_id'] = $id;
								$newmembers[] = $person;
							}
						} else {
							if (isset($ids[$i]) && $this->teams->personExists($ids[$i])) {
								$members[$ids[$i]] = null;
							}
						}
						$i++;
					}
					if ((count($ids) - $i) > 0) {
						for ($x = $i; $x <= count($ids) - 1; $x++) {
							$members[$ids[$x]] = null;
						}
					}
					$team = $this->teams->updateTeam($id, $teamdata, $members, $newmembers);
					$this->teams->commit();
					$this->flashMessage($this->translator->translate('messages.team.success.edit'));
					$this->redirect('Homepage:');
				} catch (Exception $e) {
					if ($e instanceof Nette\Application\AbortException) {
						throw $e;
					}
					Debugger::log($e);
					$form->addError('messages.team.error.edit_general');
				}
			}
		} else {
			$template = $this->template;
			$this->teams->beginTransaction();
			$i = 0;
			try {
				$password = $this->teams->generatePassword();
				$teamdata = array('name' => $form['name']->getValue(), 'message' => $form['message']->getValue(), 'ip' => $this->context->httpRequest->getRemoteAddress(), 'password' => $password);
				if(isset($form['genderclass'])) {
					$teamdata['genderclass'] = $form['genderclass']->getValue();
				}
				if(isset($form['ageclass'])) {
					$teamdata['ageclass'] = $form['ageclass']->getValue();
				}
				if(isset($form['duration'])) {
					$teamdata['duration'] = $form['duration']->getValue();
				}
				$members = array();
				$sicount = 0;
				foreach ($form['persons']->values as $person) {
					$firstname = $person['firstname'];
					if (!empty($firstname)) {
						if (!isset($address)) {
							$address = $person['email'];
						}
						if (!isset($name)) {
							$name = $person['firstname'].' '.$person['lastname'];
						}
						$person = array('firstname' => $firstname, 'lastname' => $person['lastname'], 'gender' => $person['gender'], 'country_id' => $person['country'], 'sportident' => ($person['needsportident'] ? null : $person['sportident']), 'birth' => $person['birth'], 'email' => $person['email']);
						$person['contact'] = chr(0);
						if ($i == 0) {
							$person['contact'] = chr(1);
						}
						if (isset($person['needsportident'])) {
							$sicount++;
						}
						$members[] = $person;
					}
					$i++;
				}
				$team = $this->teams->addTeam($teamdata, $members);
				$mtemplate = $this->createTemplate();
				$mtemplate->registerHelper('cost', callback($this, 'cost'));
				$mtemplate->registerHelper('categoryFormat', callback($this, 'categoryFormat'));
				
				$appDir = $this->context->parameters['appDir'];
				if (file_exists($appDir . '/templates/Mail/verification.' . $this->locale . '.latte')) {
					$mtemplate->setFile($appDir . '/templates/Mail/verification.' . $this->locale . '.latte');
				} else {
					$mtemplate->setFile($appDir . '/templates/Mail/verification.latte');
				}
				$mtemplate->team = $team;
				$mtemplate->people = $this->teams->getPersons($team['id']);
				$mtemplate->id = $team['id'];
				$mtemplate->name = $name;
				$mtemplate->password = $password;
				$mtemplate->peoplecount = count($members);
				$mtemplate->sicount = $sicount;
				$mtemplate->organiserMail = $this->context->parameters['webmasterEmail'];
				$mail =new Message;
				$mail->setFrom($mtemplate->organiserMail)
				->addTo($address)
				->setHtmlBody($mtemplate);
				
				$mailer = new SendmailMailer;
				$mailer->send($mail);
				
				$this->teams->commit();
				$this->flashMessage($this->translator->translate('messages.team.success.add', null, array('password' => $password)));
				$this->redirect('Homepage:');
			} catch (Exception $e) {
				if ($e instanceof Nette\Application\AbortException) {
					throw $e;
				}
				Debugger::log($e);
				$form->addError('messages.team.error.add_general');
			}
		}
	}


	public function createComponentTeamListFilterForm($name) {
		$form = new Form($this, $name);
		$renderer = $form->getRenderer();
		$form->setTranslator($this->translator);
		$form->setMethod("GET");
		$renderer = new \Nextras\Forms\Rendering\Bs3FormRenderer;
		$form->setRenderer($renderer);
		$form->getElementPrototype()->removeClass('form-horizontal')->addClass('form-inline');
		$renderer->wrappers['controls']['container'] = 'p';
		$renderer->wrappers['pair']['container'] = null;
		$renderer->wrappers['label']['container'] = null;
		$renderer->wrappers['control']['container'] = null;
		$renderer->wrappers['control']['errors'] = false;
		$renderer->wrappers['form']['errors'] = false;
		$renderer->wrappers['hidden']['container'] = null;

		$categories = array_keys($this->getCategories());
		$category = $form->addSelect('category', 'messages.team.list.filter.category.label', array_combine($categories, $categories))->setPrompt('messages.team.list.filter.category.all')->setAttribute('style', 'width:auto;');

		if ($this->context->httpRequest->getQuery('category')) {
			$category->setValue($this->context->httpRequest->getQuery('category'));
		}
		$category->getControlPrototype()->onchange('this.form.submit();');

		$durations = $this->context->parameters['entries']['categories']['duration'];
		if(count($durations) > 1) {
			$duration = $form->addSelect('duration', null, array_combine($durations, $durations))->setPrompt('messages.team.list.filter.duration.all')->setAttribute('style', 'width:auto;');
			if ($this->context->httpRequest->getQuery('duration')) {
				$duration->setValue($this->context->httpRequest->getQuery('duration'));
			}
			$duration->getControlPrototype()->onchange('this.form.submit();');
		}

		if ($this->user->isInRole('admin')) {
			$status = $form->addSelect('status', 'messages.team.list.filter.status.label', array('registered' => 'messages.team.list.filter.status.registered', 'paid' => 'messages.team.list.filter.status.paid'))->setPrompt('messages.team.list.filter.status.all')->setAttribute('style', 'width:auto;');
			if ($this->context->httpRequest->getQuery('status')) {
				$status->setValue($this->context->httpRequest->getQuery('status'));
			}
			$status->getControlPrototype()->onchange('this.form.submit();');
		}

		$submit = $form->addSubmit('filter', 'messages.team.list.filter.submit.label');
		$submit->getControlPrototype()->onload("this.setAttribute('style', 'display: none');");
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
		$sexes = array('male' => 'M', 'mixed' => 'X', 'female' => 'W');
		$ages = $this->context->parameters['entries']['categories']['age'];
		$categories = array();

		foreach(array_keys($sexes) as $sex) {
			foreach (array_keys($ages) as $age) {
				$categories[$sexes[$sex] . $ages[$age]['short']] = array('genderclass' => $sex, 'ageclass' => $age);
			}
		}

		return $categories;
	}
}
