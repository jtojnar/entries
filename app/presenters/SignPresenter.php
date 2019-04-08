<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Callback;

class SignPresenter extends BasePresenter {
	/** @persistent */
	public $backlink = '';

	/** @var App\Forms\FormFactory @inject */
	public $formFactory;

	/**
	 * Sign-in form factory.
	 *
	 * @return Form
	 */
	protected function createComponentSignInForm(): Form {
		$form = $this->formFactory->create();

		$form->addText('teamid', 'messages.sign.in.team_id')
			->setRequired('messages.sign.in.error.no_id');

		$form->addPassword('password', 'messages.sign.in.password')
			->setRequired('messages.sign.in.error.no_password');

		$form->addCheckbox('remember', 'messages.sign.in.remember');

		$form->addSubmit('send', 'messages.sign.in.action');

		$form->onSuccess[] = Callback::closure($this, 'signInFormSucceeded');

		return $form;
	}

	public function signInFormSucceeded(Form $form, array $values): void {
		if ($values['remember']) {
			$this->user->setExpiration('30 days', false);
		} else {
			$this->user->setExpiration('20 minutes', true);
		}

		try {
			$this->user->login($values['teamid'], $values['password']);
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
