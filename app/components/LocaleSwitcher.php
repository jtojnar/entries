<?php

declare(strict_types=1);

namespace App\Components;

use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\Template;

class LocaleSwitcher extends Control {
	/** @var array $locales */
	private $locales;

	public function __construct(array $locales) {
		parent::__construct();

		$this->locales = $locales;
	}

	public function render(): void {
		/** @var Template */
		$template = $this->template;

		$template->locales = $this->locales;

		$template->render(__DIR__ . '/LocaleSwitcher.latte');
	}
}
