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

	public function renderShow(int $id): void {
		if (!$this->user->isLoggedIn()) {
			throw new ForbiddenRequestException();
		}

		/** @var Nette\Security\SimpleIdentity $identity */
		$identity = $this->user->identity;

		/** @var Nette\Bridges\ApplicationLatte\DefaultTemplate $template */
		$template = $this->template;

		$template->invoice = $this->invoices->getById($id);

		if (!$template->invoice) {
			throw new BadRequestException();
		} elseif (!$this->user->isInRole('admin') && $identity->id !== $template->invoice->team->id) {
			throw new ForbiddenRequestException();
		}

		$template->getLatte()->addFilter('formatInvoiceStatus', Closure::fromCallable([$this, 'formatInvoiceStatus']));
		$template->getLatte()->addFilter('itemLabel', Closure::fromCallable([$this, 'itemLabel']));
	}

	private function formatInvoiceStatus(string $status): string {
		switch ($status) {
			case Invoice::STATUS_CANCELLED:
				return 'messages.billing.invoice.status.cancelled';
			case Invoice::STATUS_NEW:
				return 'messages.billing.invoice.status.new';
			case Invoice::STATUS_PAID:
				return 'messages.billing.invoice.status.paid';
			default:
				throw new \PHPStan\ShouldNotHappenException();
		}
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
