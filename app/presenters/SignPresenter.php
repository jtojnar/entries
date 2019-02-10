<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Utils\Callback;
use Nextras\Forms\Rendering\Bs4FormRenderer;

class SignPresenter extends BasePresenter {
	/** @persistent */
	public $backlink = '';

	/**
	 * Sign-in form factory.
	 *
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm() {
		$form = new Nette\Application\UI\Form();
		$form->setRenderer(new Bs4FormRenderer());
		$form->setTranslator($this->translator);

		$form->addText('teamid', 'messages.sign.in.team_id')
			->setRequired('messages.sign.in.error.no_id');

		$form->addPassword('password', 'messages.sign.in.password')
			->setRequired('messages.sign.in.error.no_password');

		$form->addCheckbox('remember', 'messages.sign.in.remember');

		$form->addSubmit('send', 'messages.sign.in.action');

		$form->onSuccess[] = Callback::closure($this, 'signInFormSucceeded');

		return $form;
	}

	public function signInFormSucceeded($form, $values): void {
		if ($values->remember) {
			$this->user->setExpiration('30 days', false);
		} else {
			$this->user->setExpiration('20 minutes', true);
		}

		try {
			$this->user->login($values->teamid, $values->password);
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function actionOut(): void {
		$this->user->logout();
		$this->flashMessage($this->translator->translate('messages.sign.out.notice'));
		$this->redirect('in');
	}
}
