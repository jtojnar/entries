<?php

declare(strict_types=1);

namespace App\Model;

use Nextras\Orm\Entity\Entity;

/**
 * Invoice.
 *
 * @property int $id {primary}
 * @property string $status {default self::STATUS_NEW} {enum self::STATUS_*}
 * @property \DateTimeImmutable $timestamp {default now}
 * @property Team $team {m:1 Team::$invoices}
 * @property array $items
 */
class Invoice extends Entity {
	public const STATUS_CANCELLED = 'cancelled';
	public const STATUS_NEW = 'new';
	public const STATUS_PAID = 'paid';

	/**
	 * @param Money $price
	 */
	public function createItem(string $name, $price): self {
		$items = $this->items;

		if (isset($items[$name])) {
			$existingPrice = $items[$name]['price'];
			if ($price !== $existingPrice) {
				throw new \Exception("This invoice item “${name}” already exists with a different price.");
			}

			return $this;
		}

		$items[$name] = ['price' => $price, 'amount' => 0];

		$this->items = $items;

		return $this;
	}

	/**
	 * @param Money $price
	 */
	public function addItem(string $name, $price = null): self {
		if (isset($price)) {
			$this->createItem($name, $price);
		}

		$items = $this->items;

		if (!isset($items[$name])) {
			throw new \Exception("Invoice item “${name}” was not defined.");
		}

		++$items[$name]['amount'];

		$this->items = $items;

		return $this;
	}

	public function addPerson(): self {
		return $this->addItem('person');
	}

	/**
	 * @return Money
	 */
	public function getTotal(array $filter = null) {
		$cost = 0;

		foreach ($this->items as $name => $item) {
			if ($filter === null || \in_array($name, $filter, true)) {
				$cost += $item['amount'] * $item['price'];
			}
		}

		return $cost;
	}
}
