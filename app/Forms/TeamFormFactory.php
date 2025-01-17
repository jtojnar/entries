<?php

declare(strict_types=1);

namespace App\Forms;

use App\Components\TeamForm;
use App\Model\Configuration\Entries;
use Nette;
use Nette\Localization\Translator;
use Nextras\FormsRendering\Renderers\Bs5FormRenderer;

final readonly class TeamFormFactory {
	use Nette\SmartObject;

	public function __construct(
		private Translator $translator,
		private Entries $entries,
	) {
	}

	/**
	 * @param string[] $countries
	 * @param array<string, int> $reservationStats
	 */
	public function create(
		array $countries,
		array $reservationStats = [],
		bool $canModifyLocked = false,
		bool $isEditing = false,
	): TeamForm {
		$form = new TeamForm(
			translator: $this->translator,
			countries: $countries,
			reservationStats: $reservationStats,
			entries: $this->entries,
			canModifyLocked: $canModifyLocked,
			isEditing: $isEditing,
		);

		$form->setTranslator($this->translator);
		$renderer = new Bs5FormRenderer();
		// We need the class to know what to hide (e.g. for applicableCategories).
		$renderer->wrappers['pair']['container'] = preg_replace('(class=")', '$0form-group ', (string) $renderer->wrappers['pair']['container']);
		$form->setRenderer($renderer);

		return $form;
	}
}
