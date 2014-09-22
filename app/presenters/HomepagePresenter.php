<?php

namespace App\Presenters;

use Nette;

class HomepagePresenter extends BasePresenter {
	public function renderDefault() {
		if ($this->user->isLoggedIn()) {
			$this->template->status = $this->user->identity->status;
		} else {
			$this->template->status = null;
		}

		$this->template->mail = $this->context->parameters['webmasterEmail'];
	}
}
