<?php

namespace App\Presenters;

use Nette;
use App\Model;

class SignPresenter extends BasePresenter {
	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm() {
		$form = new Nette\Application\UI\Form;
		$renderer = new \Nextras\Forms\Rendering\Bs3FormRenderer;
		$form->setRenderer($renderer);
		$form->setTranslator($this->translator);

		$form->addText('teamid', 'messages.sign.in.team_id')
			->setRequired('messages.sign.in.error.no_id');

		$form->addPassword('password', 'messages.sign.in.password')
			->setRequired('messages.sign.in.error.no_password');

		$form->addCheckbox('remember', 'messages.sign.in.remember');

		$form->addSubmit('send', 'messages.sign.in.action');
		
		$form->onSuccess[] = $this->signInFormSucceeded;
		return $form;
	}


	public function signInFormSucceeded($form, $values) {
		if ($values->remember) {
			$this->getUser()->setExpiration('30 days', false);
		} else {
			$this->getUser()->setExpiration('20 minutes', true);
		}

		try {
			$this->getUser()->login($values->teamid, $values->password);
			$this->redirect('Homepage:');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}


	public function actionOut() {
		$this->getUser()->logout();
		$this->flashMessage($this->translator->translate('messages.sign.out.notice'));
		$this->redirect('in');
	}
}
