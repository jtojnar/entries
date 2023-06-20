<?php

declare(strict_types=1);

namespace App\Model\Orm\Invoice;

use App\Model\Orm\Team\Team;
use Money\Money;
use Nextras\Orm\Entity\Entity;

/**
 * Invoice.
 *
 * @property int $id {primary}
 * @property string $status {default self::STATUS_NEW} {enum self::STATUS_*}
 * @property \DateTimeImmutable $timestamp {default now}
 * @property Team $team {m:1 Team::$invoices}
 * @property array $items
 *
 * @phpstan-property self::STATUS_* $status
 */
final class Invoice extends Entity {
	public const STATUS_CANCELLED = 'cancelled';
	public const STATUS_NEW = 'new';
	public const STATUS_PAID = 'paid';

	public function createItem(string $name, Money $price): self {
		$items = $this->items;

		if (isset($items[$name])) {
			$existingPrice = $items[$name]->price;
			if (!$price->equals($existingPrice)) {
				throw new \Exception("This invoice item “{$name}” already exists with a different price.");
			}

			return $this;
		}

		$items[$name] = new InvoiceItem($name, $price, 0);

		$this->items = $items;

		return $this;
	}

	public function addItem(string $name, ?Money $price = null): self {
		if (isset($price)) {
			$this->createItem($name, $price);
		}

		$items = $this->items;

		if (!isset($items[$name])) {
			throw new \Exception("Invoice item “{$name}” was not defined.");
		}

		$items[$name] = $items[$name]->addAmount(1);

		$this->items = $items;

		return $this;
	}

	public function addPerson(): self {
		return $this->addItem('person');
	}

	public function getTotal(?array $filter = null): Money {
		$relevantItems =
			$filter === null
			? $this->items
			: array_filter(
				$this->items,
				fn(string $name): bool => \in_array($name, $filter, true),
				\ARRAY_FILTER_USE_KEY
			);

		return Money::sum(
			...array_values(
				array_map(
					fn(InvoiceItem $item): Money => $item->price->multiply($item->amount),
					$relevantItems
				)
			)
		);
	}
}
