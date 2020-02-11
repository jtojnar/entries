<?php

declare(strict_types=1);

namespace App\Model;

use Money\Money;
use Nette;

/**
 * Immutable invoice item.
 *
 * @property string $name
 * @property Money $price
 * @property int $amount
 */
final class InvoiceItem implements \JsonSerializable {
	use Nette\SmartObject;

	/** @var string */
	private $name;

	/** @var Money */
	private $price;

	/** @var int */
	private $amount;

	public function __construct(string $name, Money $price, int $amount) {
		$this->name = $name;
		$this->price = $price;
		$this->amount = $amount;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getPrice(): Money {
		return $this->price;
	}

	public function getAmount(): int {
		return $this->amount;
	}

	public function setPrice(Money $price): self {
		return new self($this->name, $price, $this->amount);
	}

	public function setAmount(int $amount): self {
		return new self($this->name, $this->price, $amount);
	}

	public function addAmount(int $amount): self {
		return $this->setAmount($this->amount + $amount);
	}

	public function jsonSerialize(): array {
		return [
			'name' => $this->name,
			'price' => $this->price,
			'amount' => $this->amount,
		];
	}
}
