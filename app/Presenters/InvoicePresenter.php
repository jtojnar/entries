<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Model\Configuration\Entries;
use App\Model\Configuration\Fields;
use App\Model\Orm\Invoice\Invoice;
use Nette;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\DI\Attributes\Inject;

/**
 * Presenter for displaying invoices.
 */
final class InvoicePresenter extends BasePresenter {
	#[Inject]
	public App\Model\Orm\Team\TeamRepository $teams;

	#[Inject]
	public App\Model\Orm\Invoice\InvoiceRepository $invoices;

	#[Inject]
	public Nette\Http\Request $request;

	#[Inject]
	public App\Model\Orm\Token\TokenRepository $tokens;

	#[Inject]
	public Entries $entries;

	public function renderShow(int $id): void {
		$authorizedTeams = [];

		if (($grant = $this->request->getQuery('grant')) !== null && \assert(\is_string($grant)) && ($team = $this->tokens->getAllowedTeam($grant)) !== null) { // Assertion for PHPStan.
			$authorizedTeams[] = $team->id;
		}

		/** @var Nette\Bridges\ApplicationLatte\DefaultTemplate $template */
		$template = $this->template;
		$template->invoice = $this->invoices->getById($id);

		if ($template->invoice === null) {
			throw new BadRequestException();
		}

		if ($this->user->isLoggedIn()) {
			$identity = $this->user->identity;

			if ($this->user->isInRole('admin')) {
				$authorizedTeams[] = $template->invoice->team->id;
			} else {
				$authorizedTeams[] = $identity->getId();
			}
		}

		if (!\in_array($template->invoice->team->id, $authorizedTeams, true)) {
			throw new ForbiddenRequestException();
		}

		$template->getLatte()->addFilter('formatInvoiceStatus', $this->formatInvoiceStatus(...));
		$template->getLatte()->addFilter('itemLabel', $this->itemLabel(...));
	}

	private function formatInvoiceStatus(string $status): string {
		return match ($status) {
			Invoice::STATUS_CANCELLED => 'messages.billing.invoice.status.cancelled',
			Invoice::STATUS_NEW => 'messages.billing.invoice.status.new',
			Invoice::STATUS_PAID => 'messages.billing.invoice.status.paid',
			default => throw new \PHPStan\ShouldNotHappenException(),
		};
	}

	private function itemLabel(string $item): string {
		[$scope, $type, $key, $value] = array_merge(explode(':', $item), ['', '', '', '']);

		if ($scope === 'person' && $type === '~entry') {
			return $this->translator->translate('messages.billing.invoice.fees.person');
		}

		$fields = $scope == 'team' ? $this->entries->teamFields : $this->entries->personFields;
		$field = $fields[$key] ?? null;

		if ($field !== null && $field->label !== null) {
			$label = $this->translator->translate($field->label);
		} else {
			$label = $key;
		}

		if ($field instanceof Fields\SportidentField) {
			return $this->translator->translate('messages.billing.invoice.fees.si');
		}

		if ($field instanceof Fields\EnumField && $value !== '' && ($option = $field->options[$value] ?? null)?->label !== null) {
			return $label . ' ' . $this->translator->translate($option->label);
		}

		if ($field instanceof Fields\CheckboxlistField && $value !== '' && ($option = $field->items[$value] ?? null)?->label !== null) {
			return $label . ' ' . $this->translator->translate($option->label);
		}
		if ($field instanceof Fields\CheckboxField) {
			return $label;
		}

		return $item;
	}
}
