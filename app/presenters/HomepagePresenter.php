<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Components\LocaleSwitcher;
use Closure;
use Nette;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\DateTime;
use Nextras\FormsRendering\Renderers\FormLayout;

/**
 * Presenter for main page.
 */
class HomepagePresenter extends BasePresenter {
	/** @var App\Model\TeamRepository @inject */
	public $teams;

	/** @var App\Forms\FormFactory @inject */
	public $formFactory;

	public function renderDefault(): void {
		/** @var \Nette\Bridges\ApplicationLatte\DefaultTemplate $template */
		$template = $this->template;
		$template->invoice = null;
		$template->status = null;

		if ($this->user->isLoggedIn()) {
			/** @var \Nette\Security\SimpleIdentity $identity */
			$identity = $this->user->identity;
			$template->status = $identity->status ?? null;

			if (!$this->user->isInRole('admin')) {
				$team = $this->teams->getById($identity->getId());
				if ($team === null) {
					$this->user->logout(true);
				} else {
					$template->invoice = $team->lastInvoice;
				}
			}
		}

		$template->registrationOpen = !($this->context->parameters['entries']['closing']->diff(new DateTime())->invert === 0 || $this->context->parameters['entries']['opening']->diff(new DateTime())->invert === 1);
		$template->allowLateRegistrationsByEmail = $this->context->parameters['entries']['allowLateRegistrationsByEmail'];
		$template->mail = $this->context->parameters['webmasterEmail'];
	}

	protected function createComponentMaintenanceForm(): Form {
		$form = $this->formFactory->create(FormLayout::INLINE);

		$clearCacheButton = $form->addSubmit('clearCache', 'messages.maintenance.clear_cache');
		$clearCacheButton->controlPrototype->removeClass('btn-primary')->addClass('btn-warning');
		/** @var callable(SubmitButton): void */
		$clearCache = Closure::fromCallable([$this, 'clearCache']);
		$clearCacheButton->onClick[] = $clearCache;

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
