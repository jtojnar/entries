<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Model\Invoice;
use Nette;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Utils\Callback;

class InvoicePresenter extends BasePresenter {
	/** @var App\Model\TeamRepository @inject */
	public $teams;

	/** @var App\Model\InvoiceRepository @inject */
	public $invoices;

	public function renderShow(int $id): void {
		/** @var Nette\Security\Identity $identity */
		$identity = $this->user->identity;

		/** @var Nette\Bridges\ApplicationLatte\Template $template */
		$template = $this->template;

		$template->invoice = $this->invoices->getById($id);

		if (!$template->invoice) {
			throw new BadRequestException();
		} elseif (!$this->user->isInRole('admin') && $identity->id !== $template->invoice->team->id) {
			throw new ForbiddenRequestException();
		}

		$template->getLatte()->addFilter('formatInvoiceStatus', Callback::closure($this, 'formatInvoiceStatus'));
		$template->getLatte()->addFilter('itemLabel', Callback::closure($this, 'itemLabel'));
	}

	public function formatInvoiceStatus($status) {
		switch ($status) {
			case Invoice::STATUS_CANCELLED:
				return 'messages.billing.invoice.status.cancelled';
			case Invoice::STATUS_NEW:
				return 'messages.billing.invoice.status.new';
			case Invoice::STATUS_PAID:
				return 'messages.billing.invoice.status.paid';
		}
	}

	public function itemLabel($item) {
		[$scope, $type, $key, $value] = array_merge(explode(':', $item), ['', '', '', '']);

		if ($scope === 'person' && $type === '~entry') {
			return $this->translator->translate('messages.billing.invoice.fees.person');
		}

		if ($type === 'sportident') {
			return $this->translator->translate('messages.billing.invoice.fees.si');
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

		if ($type === 'checkboxlist' && !empty($value)) {
			return $label . ': ' . $field['items'][$value]['label'][$this->locale];
		}

		return $item;
	}
}
