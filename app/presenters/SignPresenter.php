<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use Closure;
use Contributte\Translation\Wrappers\NotTranslate;
use Nette;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Security\IAuthenticator;
use Nette\Security\IUserStorage;

/**
 * Presenter for signing in and out.
 */
class SignPresenter extends BasePresenter {
	/** @var string @persistent */
	public $backlink = '';

	/** @var App\Forms\FormFactory @inject */
	public $formFactory;

	/** @var App\Model\TeamManager @inject */
	public $teamManager;

	/** @var App\Model\TeamRepository @inject */
	public $teams;

	/**
	 * Sign-in form factory.
	 */
	protected function createComponentSignInForm(): Form {
		$form = $this->formFactory->create();

		$form->addText('teamid', 'messages.sign.in.team_id')
			->setRequired('messages.sign.in.error.no_id');

		$form->addPassword('password', 'messages.sign.in.password')
			->setRequired('messages.sign.in.error.no_password');

		$form->addCheckbox('remember', 'messages.sign.in.remember');

		$form->addSubmit('send', 'messages.sign.in.action');

		/** @var callable(Nette\Forms\Form, array): void */
		$signInFormSucceeded = Closure::fromCallable([$this, 'signInFormSucceeded']);
		$form->onSuccess[] = $signInFormSucceeded;

		return $form;
	}

	private function signInFormSucceeded(Form $form, array $values): void {
		if ($values['remember']) {
			$this->user->setExpiration('30 days');
		} else {
			$this->user->setExpiration('20 minutes', IUserStorage::CLEAR_IDENTITY);
		}

		try {
			$this->user->login($values['teamid'], $values['password']);
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError(
				$e->getCode() === IAuthenticator::IDENTITY_NOT_FOUND
				? 'messages.sign.in.error.incorrect_id'
				: ($e->getCode() === IAuthenticator::INVALID_CREDENTIAL
				? 'messages.sign.in.error.incorrect_password'
				: new NotTranslate($e->getMessage()))
			);
		}
	}

	public function actionOut(): void {
		$this->user->logout(true);
		$this->flashMessage($this->translator->translate('messages.sign.out.notice'));
		$this->redirect('in');
	}

	public function actionAs(int $teamId): void {
		if (!$this->user->isInRole('admin')) {
			throw new ForbiddenRequestException();
		}

		$team = $this->teams->getById($teamId);
		if ($team === null) {
			throw new BadRequestException();
		}

		$identity = $this->teamManager->createUserIdentity($team);
		$this->user->login($identity);

		$this->flashMessage(
			$this->translator->translate('messages.sign.as.notice', ['id' => $teamId])
		);
		$this->redirect('Homepage:');
	}
}
