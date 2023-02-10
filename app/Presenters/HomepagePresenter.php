<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Components\LocaleSwitcher;
use App\Model\Configuration\Entries;
use Contributte\Translation\Wrappers\NotTranslate;
use DateTimeImmutable;
use Nette;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\Forms\Controls\SubmitButton;
use Nextras\FormsRendering\Renderers\FormLayout;

/**
 * Presenter for main page.
 */
final class HomepagePresenter extends BasePresenter {
	#[Inject]
	public Entries $entries;

	#[Inject]
	public App\Model\Orm\Message\MessageRepository $messages;

	#[Inject]
	public App\Model\Orm\Team\TeamRepository $teams;

	#[Inject]
	public App\Forms\FormFactory $formFactory;

	public function renderDefault(): void {
		/** @var \Nette\Bridges\ApplicationLatte\DefaultTemplate $template */
		$template = $this->template;
		$template->invoice = null;
		$template->status = null;

		if ($this->user->isLoggedIn()) {
			/** @var \Nette\Security\SimpleIdentity $identity */
			$identity = $this->user->identity;

			if (!$this->user->isInRole('admin')) {
				$team = $this->teams->getById($identity->getId());
				if ($team === null) {
					$this->user->logout(true);
				} else {
					$template->status = $team->status;
					$template->invoice = $team->lastInvoice;
				}
			}
		}

		$today = new DateTimeImmutable();
		$template->registrationOpen = $this->entries->closing > $today && $this->entries->opening < $today;
		$template->allowLateRegistrationsByEmail = $this->entries->allowLateRegistrationsByEmail;
		$template->mail = $this->context->parameters['webmasterEmail'];
	}

	protected function createComponentMaintenanceForm(): Form {
		$form = $this->formFactory->create(FormLayout::INLINE);

		$clearCacheButton = $form->addSubmit('clearCache', 'messages.maintenance.clear_cache');
		$clearCacheButton->controlPrototype->removeClass('btn-primary')->addClass('btn-warning');
		$clearCacheButton->onClick[] = $this->clearCache(...);

		$queuedMessageCount = $this->messages->findBy([
			'status' => App\Model\Orm\Message\Message::STATUS_QUEUED,
		])->countStored();

		if ($queuedMessageCount > 0) {
			$sendMessagesButton = $form->addSubmit(
				'sendMessages',
				new NotTranslate(
					$this->translator->translate(
						'messages.maintenance.send_outbound_messages',
						[
							'count' => $queuedMessageCount,
						],
					),
				),
			);
			$sendMessagesButton->onClick[] = function(SubmitButton $btn): void {
				$this->redirect('Communication:send');
			};
		}

		return $form;
	}

	private function clearCache(SubmitButton $form): void {
		if (!$this->user->isInRole('admin')) {
			throw new ForbiddenRequestException();
		}

		/** @var \IteratorAggregate<string, \SplFileInfo> */ // For PHPStan.
		$cacheIterator = Nette\Utils\Finder::find('*')->from($this->context->parameters['tempDir'] . '/cache')->childFirst();
		foreach ($cacheIterator as $entry) {
			$path = (string) $entry;
			if ($entry->isDir()) { // collector: remove empty dirs
				@rmdir($path); // @ - removing dirs is not necessary
				continue;
			}
			unlink($path);
		}

		$this->flashMessage($this->translator->translate('messages.maintenance.cache_cleared'));
		$this->redirect('Homepage:');
	}

	protected function createComponentLocaleSwitcher(): LocaleSwitcher {
		/** @var \Contributte\Translation\Translator */
		$translator = $this->translator;

		$localeNames = $this->context->parameters['locales'];

		$allowedLocales = $translator->getLocalesWhitelist();

		return new LocaleSwitcher($localeNames, $allowedLocales);
	}
}
