<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Model\TeamManager;
use Contributte\Translation\Wrappers\NotTranslate;
use Nette;
use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\Security\Authenticator;

/**
 * Presenter for signing in and out.
 */
final class SignPresenter extends BasePresenter {
	#[Persistent]
	public string $backlink = '';

	#[Inject]
	public App\Forms\FormFactory $formFactory;

	#[Inject]
	public TeamManager $teamManager;

	#[Inject]
	public App\Model\Orm\Team\TeamRepository $teams;

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

		/** @var callable(Form, mixed): void */ // For PHPStan, Nette will convert the value to the correct one (array) based on argument type.
		$signInFormSucceeded = $this->signInFormSucceeded(...);
		$form->onSuccess[] = $signInFormSucceeded;

		return $form;
	}

	private function signInFormSucceeded(Form $form, array $values): void {
		if ($values['remember']) {
			$this->user->setExpiration('30 days');
		} else {
			$this->user->setExpiration('20 minutes', true);
		}

		try {
			$this->user->login($values['teamid'], $values['password']);
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError(
				match ($e->getCode()) {
					Authenticator::IDENTITY_NOT_FOUND => 'messages.sign.in.error.incorrect_id',
					Authenticator::INVALID_CREDENTIAL => 'messages.sign.in.error.incorrect_password',
					TeamManager::ENTRY_WITHDRAWN => 'messages.team.error.withdrawn',
					default => new NotTranslate($e->getMessage()),
				}
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
