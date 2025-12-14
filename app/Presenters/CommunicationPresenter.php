<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Helpers\EmailFactory;
use App\Model\Configuration\Entries;
use App\Model\Orm\Team\Team;
use Exception;
use Latte;
use Nette;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\Forms\Controls\SubmitButton;
use Nextras\FormsRendering\Renderers\Bs5FormRenderer;
use Throwable;
use Tracy\Debugger;

/**
 * Presenter for signing in and out.
 */
final class CommunicationPresenter extends BasePresenter {
	#[Inject]
	public App\Forms\FormFactory $formFactory;

	#[Inject]
	public Nette\Bridges\ApplicationLatte\LatteFactory $latteFactory;

	#[Inject]
	public Nette\Mail\Mailer $mailer;

	#[Inject]
	public App\Model\Orm\Message\MessageRepository $messages;

	#[Inject]
	public Nette\Http\Request $request;

	#[Inject]
	public App\Model\Orm\Team\TeamRepository $teams;

	#[Inject]
	public App\Model\Orm\Token\TokenRepository $tokens;

	#[Inject]
	public EmailFactory $emailFactory;

	#[Inject]
	public Entries $entries;

	/**
	 * Message composition form factory.
	 */
	protected function createComponentComposeForm(): Form {
		$form = $this->formFactory->create();

		$form->addText('recipients', 'messages.communication.compose.recipients.label')
			->setHtmlAttribute('placeholder', 'messages.communication.compose.recipients.placeholder')
			->setRequired('messages.communication.compose.recipients.error.empty')
			->setDefaultValue($this->request->getQuery('ids'));

		$form->addText('subject', 'messages.communication.compose.subject.label')
			->setRequired('messages.communication.compose.subject.error.empty');

		$organiserMail = $this->parameters->getWebmasterEmail();
		$form->addEmail('sender', 'messages.communication.compose.sender.label')
			->setRequired('messages.communication.compose.sender.error.empty')
			->setDefaultValue($organiserMail);

		$body = $form->addTextArea('body', 'messages.communication.compose.body.label')
			->setHtmlAttribute('rows', 15)
			->setRequired('messages.communication.compose.body.error.empty');

		$body->getControlPrototype()->class[] = 'codemirror';

		$preview = $form->addSubmit('preview', 'messages.communication.compose.preview');
		$preview->onClick[] = $this->composeFormPreview(...);

		$enquee = $form->addSubmit('enqueue', 'messages.communication.compose.enqueue');
		$enquee->onClick[] = $this->composeFormEnqueue(...);

		/** @var Bs5FormRenderer */
		$renderer = $form->renderer;
		$renderer->primaryButton = $enquee;

		return $form;
	}

	private function composeFormPreview(SubmitButton $button): void {
		if (!$this->user->isInRole('admin')) {
			throw new ForbiddenRequestException();
		}

		$form = $button->form;

		/** @var array */ // actually \ArrayAccess but PHPStan does not handle that very well.
		$values = $form->getValues();

		$teamsIds = explode(',', (string) $values['recipients']);
		$teamsIds = array_map(
			trim(...),
			$teamsIds,
		);
		$teamsIds = array_filter(
			$teamsIds,
			static fn(string $id): bool => $id !== '',
		);
		/** @var int[] */
		$teamsIds = array_map(
			static fn(string $id): int => (int) $id,
			$teamsIds,
		);

		$teams = array_combine(
			$teamsIds,
			array_map(
				fn(int $teamId): ?Team => $this->teams->getById($teamId),
				$teamsIds
			)
		);

		$nullTeamIds = array_keys(
			array_filter(
				$teams,
				fn(?Team $team): bool => $team === null
			)
		);

		if (\count($nullTeamIds) > 0) {
			$form->addError(
				$this->translator->translate(
					'messages.communication.compose.error.unknown_id',
					[
						'count' => \count($nullTeamIds),
						'ids' => implode(', ', $nullTeamIds),
					]
				),
				false,
			);

			return;
		}

		try {
			foreach ($teams as $team) {
				\assert($team !== null); // For PHPStan.
				$grant = $this->tokens->createForTeam($team);
				$this->template->previewMessage = $this->_renderMessageBody(
					team: $team,
					subject: $values['subject'],
					grant: $grant,
					body: $values['body'],
				);
				break;
			}
		} catch (Throwable $e) {
			Debugger::log($e);
			$form->addError(
				$this->translator->translate(
					'messages.communication.compose.error.preview.template',
					[
						'error' => $e->getMessage(),
					]
				),
				false,
			);
		}
	}

	private function composeFormEnqueue(SubmitButton $button): void {
		if (!$this->user->isInRole('admin')) {
			throw new ForbiddenRequestException();
		}

		$form = $button->form;

		/** @var array */ // actually \ArrayAccess but PHPStan does not handle that very well.
		$values = $form->getValues();
		$subject = $values['subject'];

		$teamsIds = explode(',', (string) $values['recipients']);
		$teamsIds = array_map(
			trim(...),
			$teamsIds,
		);
		$teamsIds = array_filter(
			$teamsIds,
			static fn(string $id): bool => $id !== '',
		);
		/** @var int[] */
		$teamsIds = array_map(
			static fn(string $id): int => (int) $id,
			$teamsIds,
		);

		$teams = array_combine(
			$teamsIds,
			array_map(
				fn(int $teamId): ?Team => $this->teams->getById($teamId),
				$teamsIds
			)
		);

		$nullTeamIds = array_keys(
			array_filter(
				$teams,
				fn(?Team $team): bool => $team === null
			)
		);

		if (\count($nullTeamIds) > 0) {
			$form->addError(
				$this->translator->translate(
					'messages.communication.compose.error.unknown_id',
					[
						'count' => \count($nullTeamIds),
						'ids' => implode(', ', $nullTeamIds),
					]
				),
				false,
			);

			return;
		}

		try {
			foreach ($teams as $team) {
				\assert($team !== null); // For PHPStan.

				$grant = $this->tokens->createForTeam($team);
				$body = $this->_renderMessageBody(
					team: $team,
					subject: $subject,
					grant: $grant,
					body: $values['body'],
				);

				$message = new App\Model\Orm\Message\Message();
				$message->team = $team;
				$message->subject = $subject;
				$message->sender = $values['sender'];
				$message->body = $body;
				$this->messages->persist($message);
			}

			$this->tokens->flush();
			$this->messages->flush();
			$this->flashMessage(
				$this->translator->translate(
					'messages.communication.compose.enqueue.succeeded',
					[
						'count' => \count($teams),
					]
				),
				'info'
			);
			$this->redirect('Team:list');
		} catch (Nette\Application\AbortException $e) {
			throw $e;
		} catch (Throwable $e) {
			Debugger::log($e);
			$form->addError(
				$this->translator->translate(
					'messages.communication.compose.error.enqueue_failed',
					[
						'count' => \count($teams),
						'error' => $e->getMessage(),
					]
				),
				false,
			);
		}
	}

	private function _renderMessageBody(Team $team, string $subject, string $grant, string $body): string {
		$latte = $this->latteFactory->create();

		$layout = file_get_contents(__DIR__ . '/templates/Mail/@layout.latte');
		\assert(\is_string($layout));

		$latte->setLoader(new Latte\Loaders\StringLoader([
			'@layout.latte' => $layout,
			'message.latte' => '{layout @layout.latte}{block body}' . $body . '{/block}',
		]));

		$latte->addExtension(new Nette\Bridges\ApplicationLatte\UIExtension($this));

		$messageHtml = $latte->renderToString(
			'message.latte',
			new templates\Mail\Message(
				// Define variables for use in the e-mail template.
				accountNumber: $this->parameters->accountNumber,
				accountNumberIban: $this->parameters->accountNumberIban !== null ? $this->parameters->accountNumberIban->asString() : null,
				eventName: $eventName = $this->parameters->getSiteTitle($this->locale)
					?? $this->parameters->getSiteTitle($this->translator->getDefaultLocale())
					?? throw new \PHPStan\ShouldNotHappenException(),
				eventNameShort: $this->parameters->getSiteTitleShort($this->locale)
					?? $this->parameters->getSiteTitleShort($this->translator->getDefaultLocale())
					?? $eventName,
				dateFormat: $this->translator->translate('messages.email.verification.entry_details.person.birth.format'),
				team: $team,
				people: $team->persons,
				id: $team->id,
				name: iterator_to_array($team->persons)[0]->firstname
				?? throw new \PHPStan\ShouldNotHappenException(),
				invoice: $team->lastInvoice,
				organiserMail: $this->parameters->getWebmasterEmail(),
				subject: $subject,
				grant: $grant,
			),
		);

		\assert($messageHtml !== '');

		// Inline styles into the e-mail
		$messageHtml = $this->emailFactory->create($messageHtml);

		return $messageHtml;
	}

	public function actionCompose(): void {
		if (!$this->user->isInRole('admin')) {
			throw new ForbiddenRequestException();
		}
	}

	public function actionList(?int $id = null): void {
		$identity = $this->user->identity;

		if ($id === null) {
			$this->redirect('this', ['id' => $identity->getId()]);
		}

		if (!$this->user->isInRole('admin') && $identity->getId() !== $id) {
			$backlink = $this->storeRequest('+ 48 hours');
			$this->redirect('Sign:in', ['backlink' => $backlink]);
		}

		$team = $this->teams->getById($id);
		if ($team === null) {
			throw new BadRequestException();
		}

		$this->template->team = $team;
		$this->template->messages = $team->messages;
	}

	public function actionView(int $id): void {
		$authorizedTeams = [];

		if (($grant = $this->request->getQuery('grant')) !== null && \assert(\is_string($grant)) && ($team = $this->tokens->getAllowedTeam($grant)) !== null) { // Assertion for PHPStan.
			$authorizedTeams[] = $team->id;
		}

		$message = $this->messages->getById($id);

		if ($message === null) {
			throw new BadRequestException();
		}

		if ($this->user->isLoggedIn()) {
			$identity = $this->user->identity;

			if ($this->user->isInRole('admin')) {
				$authorizedTeams[] = $message->team->id;
			} else {
				$authorizedTeams[] = $identity->getId();
			}
		}

		if (!\in_array($message->team->id, $authorizedTeams, true)) {
			$backlink = $this->storeRequest('+ 48 hours');
			$this->redirect('Sign:in', ['backlink' => $backlink]);
		}

		$this->template->message = $message;
	}

	public function actionSend(): void {
		if (!$this->user->isInRole('admin')) {
			throw new ForbiddenRequestException();
		}

		$total = null;
		$count = 0;
		try {
			$messages = $this->messages->findBy([
				'status' => App\Model\Orm\Message\Message::STATUS_QUEUED,
			]);

			/** @throws Exception */
			$total = $messages->countStored();

			foreach ($messages as $message) {
				// Inline styles into the e-mail
				$mailHtml = $message->body;
				\assert($mailHtml !== '');
				$mailHtml = $this->emailFactory->create($mailHtml);

				$mail = new Nette\Mail\Message();
				$firstMemberAddress = iterator_to_array($message->team->persons)[0]->email;
				$mail->setFrom($message->sender)->addTo($firstMemberAddress)->setHtmlBody($mailHtml);

				$mailer = $this->mailer;
				$mailer->send($mail);

				$message->status = App\Model\Orm\Message\Message::STATUS_SENT;
				$this->messages->persistAndFlush($message);

				++$count;
			}

			if ($count === 0) {
				$this->flashMessage(
					$this->translator->translate(
						'messages.communication.send.no_messages',
					),
				);
			} else {
				$this->flashMessage(
					$this->translator->translate(
						'messages.communication.send.success',
						[
							'count' => $count,
						]
					),
					'success',
				);
			}

			$this->redirect('Homepage:');
		} catch (Nette\Application\AbortException $e) {
			throw $e;
		} catch (Throwable $e) {
			Debugger::log($e);
			Debugger::barDump($e);
			if ($total === null) {
				$this->flashMessage(
					$this->translator->translate(
						'messages.communication.send.error.message_collection_failed',
					),
					'danger',
				);
			} else {
				$this->flashMessage(
					$this->translator->translate(
						'messages.communication.send.error.submit_failed',
						[
							'count' => $total - $count,
							'total' => $total,
						]
					),
					'danger',
				);
			}

			$this->redirect('Homepage:');
		}
	}
}
