<?php

declare(strict_types=1);

namespace App\Model\Orm\Invoice;

use JsonSerializable;
use Money\Money;

/**
 * Immutable invoice item.
 */
final class InvoiceItem implements JsonSerializable {
	public function __construct(
		private readonly string $name,
		private readonly Money $price,
		private readonly int $amount,
	) {
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

	public function withPrice(Money $price): self {
		return new self($this->name, $price, $this->amount);
	}

	public function withAmount(int $amount): self {
		return new self($this->name, $this->price, $amount);
	}

	public function withAmountAdded(int $amount): self {
		return $this->withAmount($this->amount + $amount);
	}

	public function jsonSerialize(): array {
		return [
			'name' => $this->getName(),
			'price' => $this->getPrice(),
			'amount' => $this->getAmount(),
		];
	}
}
