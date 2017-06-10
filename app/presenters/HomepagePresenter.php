<?php

namespace App\Presenters;

use Nette;
use Nette\Utils\DateTime;

class HomepagePresenter extends BasePresenter {
	public function renderDefault() {
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
		$template->locales = count($locales) > 1 ? $locales : [];

		$template->registrationOpen = !($this->context->parameters['entries']['closing']->diff(new DateTime())->invert === 0 || $this->context->parameters['entries']['opening']->diff(new DateTime())->invert === 1);
		$template->mail = $this->context->parameters['webmasterEmail'];
	}
}
