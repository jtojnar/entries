<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;

/**
 * Invoice
 *
 * @property int $id {primary}
 * @property string $status {default self::STATUS_NEW} {enum self::STATUS_*}
 * @property DateTime $timestamp {default now}
 * @property Team $team {m:1 Team::$invoices}
 * @property array $items
 */
class Invoice extends Entity {
	const STATUS_CANCELLED = 'cancelled';
	const STATUS_NEW = 'new';
	const STATUS_PAID = 'paid';

	public function createItem($name, $price) {
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

	public function addItem($name, $price = null) {
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

	public function addPerson() {
		return $this->addItem('person');
	}

	public function getTotal(array $filter = null) {
		$cost = 0;

		foreach ($this->items as $name => $item) {
			if ($filter === null || in_array($name, $filter)) {
				$cost += $item['amount'] * $item['price'];
			}
		}

		return $cost;
	}
}
