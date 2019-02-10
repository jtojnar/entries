<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Caching\Cache;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Callback;
use Nette\Utils\DateTime;
use Nextras\Forms\Rendering\Bs4FormRenderer;
use Nextras\Forms\Rendering\FormLayout;

class HomepagePresenter extends BasePresenter {
	/** @var Nette\Caching\IStorage @inject */
	public $storage;

	public function renderDefault(): void {
		/** @var Nette\Bridges\ApplicationLatte\Template $template */
		$template = $this->template;

		if ($this->user->isLoggedIn()) {
			/** @var Nette\Security\Identity $identity */
			$identity = $this->user->identity;
			$template->status = $identity->status;
		} else {
			$template->status = null;
		}

		$locales = $this->context->parameters['locales'];
		$template->locales = \count($locales) > 1 ? $locales : [];

		$template->registrationOpen = !($this->context->parameters['entries']['closing']->diff(new DateTime())->invert === 0 || $this->context->parameters['entries']['opening']->diff(new DateTime())->invert === 1);
		$template->mail = $this->context->parameters['webmasterEmail'];
	}

	/**
	 * Maintenance form factory.
	 *
	 * @return Form
	 */
	protected function createComponentMaintenanceForm(): Form {
		$form = new Form();
		$form->setRenderer(new Bs4FormRenderer(FormLayout::INLINE));

		$form->setTranslator($this->translator);

		$clearCacheButton = $form->addSubmit('clearCache', 'messages.maintenance.clear_cache');
		$clearCacheButton->controlPrototype->removeClass('btn-primary')->addClass('btn-warning');
		$clearCacheButton->onClick[] = Callback::closure($this, 'clearCache');

		return $form;
	}

	public function clearCache(SubmitButton $form, array $values): void {
		if (!$this->user->isInRole('admin')) {
			throw new ForbiddenRequestException();
		}

		$this->storage->clean([
			Cache::ALL => true,
		]);

		$this->flashMessage($this->translator->translate('messages.maintenance.cache_cleared'));
		$this->redirect('Homepage:');
	}
}
