<?php

namespace App\Presenters;

use Nette;
use Nette\Utils\DateTime;

class HomepagePresenter extends BasePresenter {
	public function renderDefault() {
		if ($this->user->isLoggedIn()) {
			$this->template->status = $this->user->identity->status;
		} else {
			$this->template->status = null;
		}

		$locales = $this->context->parameters['locales'];
		$this->template->locales = count($locales) > 1 ? $locales : [];

		$this->template->registrationOpen = !($this->context->parameters['entries']['closing']->diff(new DateTime())->invert == 0 || $this->context->parameters['entries']['opening']->diff(new DateTime())->invert == 1);
		$this->template->mail = $this->context->parameters['webmasterEmail'];
	}
}
