<?php

namespace App\Model;

use App;
use Nette;

class Invoice extends Nette\Object {
	private $items = [];

	public function createItem($name, $price) {
		if (isset($this->items[$name])) {
			if ($price !== $this->items[$name]['price']) {
				throw new \Exception('This invoice item already exists.');
			}

			return $this;
		}

		$this->items[$name] = ['price' => $price, 'amount' => 0];

		return $this;
	}

	public function addItem($name, $price = null) {
		if (isset($price)) {
			$this->createItem($name, $price);
		}

		if (!isset($this->items[$name])) {
			throw new \Exception('Invoice item was not defined.');
		}

		++$this->items[$name]['amount'];

		return $this;
	}

	public function addPerson() {
		return $this->addItem('person');
	}

	public function getTotal() {
		$cost = 0;

		foreach ($this->items as $name => $item) {
			$cost += $item['amount'] * $item['price'];
		}

		return $cost;
	}
}
