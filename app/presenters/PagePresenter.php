<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use Nette;

/**
 * Presenter for signing in and out.
 */
class PagePresenter extends BasePresenter {
	/** @var App\Forms\FormFactory @inject */
	public $formFactory;

	/** @var Nette\Bridges\ApplicationLatte\ILatteFactory @inject */
	public $latteFactory;

	/** @var \Nette\Mail\Mailer @inject */
	public $mailer;

	/** @var App\Model\MessageRepository @inject */
	public $messages;

	/** @var \Nette\Http\Request @inject */
	public $request;

	/** @var App\Model\TeamRepository @inject */
	public $teams;

	protected function startup(): void {
		parent::startup();

		if (!$this->user->isLoggedIn() || $this->user->isInRole('admin')) {
			$this->redirect('Sign:in');
		}

		/** @var \Nette\Security\SimpleIdentity $identity */
		$identity = $this->user->identity;
		$team = $this->teams->getById($identity->getId());

		if ($team === null) {
			$this->user->logout(true);
			$this->redirect('Sign:in');
		}

		$this->template->accountNumber = $this->context->parameters['entries']['accountNumber'];
		$this->template->eventName = $eventName = $this->parameters->getSiteTitle($this->locale)
			?? $this->parameters->getSiteTitle($this->translator->getDefaultLocale())
			?? throw new \PHPStan\ShouldNotHappenException();
		$this->template->eventNameShort = $this->parameters->getSiteTitleShort($this->locale)
			?? $this->parameters->getSiteTitleShort($this->translator->getDefaultLocale())
			?? $eventName;
		$this->template->dateFormat = $this->translator->translate('messages.email.verification.entry_details.person.birth.format');
		$this->template->team = $team;
		$this->template->people = $team->persons;
		$this->template->id = $team->id;
		$this->template->name = iterator_to_array($team->persons)[0]->firstname
		?? throw new \PHPStan\ShouldNotHappenException();
		$this->template->invoice = $team->lastInvoice;
		$this->template->organiserMail = $this->context->parameters['webmasterEmail'];
	}
}
