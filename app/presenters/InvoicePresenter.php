<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Model\Invoice;
use Closure;
use Nette;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;

/**
 * Presenter for displaying invoices.
 */
class InvoicePresenter extends BasePresenter {
	/** @var App\Model\TeamRepository @inject */
	public $teams;

	/** @var App\Model\InvoiceRepository @inject */
	public $invoices;

	/** @var \Nette\Http\Request @inject */
	public $request;

	/** @var App\Model\TokenRepository @inject */
	public $tokens;

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
			/** @var Nette\Security\SimpleIdentity $identity */
			$identity = $this->user->identity;

			if ($this->user->isInRole('admin')) {
				$authorizedTeams[] = $template->invoice->team->id;
			} else {
				$authorizedTeams[] = $identity->id;
			}
		}

		if (!\in_array($template->invoice->team->id, $authorizedTeams, true)) {
			throw new ForbiddenRequestException();
		}

		$template->getLatte()->addFilter('formatInvoiceStatus', Closure::fromCallable([$this, 'formatInvoiceStatus']));
		$template->getLatte()->addFilter('itemLabel', Closure::fromCallable([$this, 'itemLabel']));
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
			return (string) $this->translator->translate('messages.billing.invoice.fees.person');
		}

		if ($type === 'sportident') {
			return (string) $this->translator->translate('messages.billing.invoice.fees.si');
		}

		$field = $this->presenter->context->parameters['entries']['fields'][$scope][$key] ?? null;

		if (isset($field) && isset($field['label'][$this->locale])) {
			$label = $field['label'][$this->locale];
		} else {
			$label = $key;
		}

		if ($type === 'enum' && !empty($value) && isset($field['options'][$value]['label'][$this->locale])) {
			return $label . ': ' . $field['options'][$value]['label'][$this->locale];
		}

		if ($type === 'checkboxlist' && !empty($value) && isset($field['items'][$value]['label'][$this->locale])) {
			return $label . ': ' . $field['items'][$value]['label'][$this->locale];
		}

		return $item;
	}
}
