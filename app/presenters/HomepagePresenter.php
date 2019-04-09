<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use Closure;
use Nette;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\DateTime;
use Nextras\Forms\Rendering\FormLayout;

class HomepagePresenter extends BasePresenter {
	/** @var App\Model\TeamRepository @inject */
	public $teams;

	/** @var App\Forms\FormFactory @inject */
	public $formFactory;

	public function renderDefault(): void {
		/** @var Nette\Bridges\ApplicationLatte\Template $template */
		$template = $this->template;

		if ($this->user->isLoggedIn()) {
			/** @var Nette\Security\Identity $identity */
			$identity = $this->user->identity;
			$template->status = $identity->status;

			if ($template->status === 'registered') {
				$template->invoice = $this->teams->getById($identity->getId())->lastInvoice;
			}
		} else {
			$template->status = null;
		}

		$locales = $this->context->parameters['locales'];
		$template->locales = \count($locales) > 1 ? $locales : [];

		$template->registrationOpen = !($this->context->parameters['entries']['closing']->diff(new DateTime())->invert === 0 || $this->context->parameters['entries']['opening']->diff(new DateTime())->invert === 1);
		$template->allowLateRegistrationsByEmail = $this->context->parameters['entries']['allowLateRegistrationsByEmail'];
		$template->mail = $this->context->parameters['webmasterEmail'];
	}

	protected function createComponentMaintenanceForm(): Form {
		$form = $this->formFactory->create(FormLayout::INLINE);

		$clearCacheButton = $form->addSubmit('clearCache', 'messages.maintenance.clear_cache');
		$clearCacheButton->controlPrototype->removeClass('btn-primary')->addClass('btn-warning');
		$clearCacheButton->onClick[] = Closure::fromCallable([$this, 'clearCache']);

		return $form;
	}

	private function clearCache(SubmitButton $form): void {
		if (!$this->user->isInRole('admin')) {
			throw new ForbiddenRequestException();
		}

		foreach (Nette\Utils\Finder::find('*')->from($this->context->parameters['tempDir'] . '/cache')->childFirst() as $entry) {
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
}
