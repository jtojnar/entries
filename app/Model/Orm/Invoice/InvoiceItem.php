<?php

declare(strict_types=1);

namespace App\Model\Orm\Invoice;

use JsonSerializable;
use Money\Money;
use Override;

/**
 * Immutable invoice item.
 */
final readonly class InvoiceItem implements JsonSerializable {
	public function __construct(
		private string $name,
		private Money $price,
		private int $amount,
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

	#[Override]
	public function jsonSerialize(): array {
		return [
			'name' => $this->getName(),
			'price' => $this->getPrice(),
			'amount' => $this->getAmount(),
		];
	}
}
