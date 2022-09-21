<?php

declare(strict_types=1);

namespace App\Model;

use Money\Currency;
use Money\Money;
use Nette;
use Nette\Schema\Expect;
use Nette\Utils\Json;
use Nextras\Orm\Mapper\Dbal\Conventions\IConventions;

final class InvoiceMapper extends BaseMapper {
	protected function createConventions(): IConventions {
		$conventions = parent::createConventions();

		$processor = new Nette\Schema\Processor();
		$itemsSchema = Expect::arrayOf(
			Expect::structure([
				'name' => Expect::string(),
				'price' => Expect::structure([
					'amount' => Expect::string(),
					'currency' => Expect::string(),
				])->castTo('array'),
				'amount' => Expect::int(),
			])->castTo('array'),
		);

		$conventions->setMapping(
			'items',
			$conventions->convertEntityToStorageKey('items'),
			/**
			 * Convert JSON object of the following form into a list of invoice items
			 * [{name: string, price: {amount: string, currency: string}, amount: int}].
			 *
			 * @return InvoiceItem[]
			 */
			function(mixed $value, string $newKey) use ($processor, $itemsSchema): array {
				\assert(\is_string($value)); // For PHPStan.
				$data = Json::decode($value, Json::FORCE_ARRAY);

				/** @var array{name: string, price: array{amount: numeric-string, currency: non-empty-string}, amount: int}[] */ // For PHPStan.
				$items = $processor->process($itemsSchema, $data);
				$result = [];

				foreach ($items as ['name' => $name, 'price' => $price, 'amount' => $amount]) {
					if (isset($result[$name])) {
						throw new \Exception("Duplicate invoice item ‘{$name}’");
					}

					$price = new Money($price['amount'], new Currency($price['currency']));
					$result[$name] = new InvoiceItem($name, $price, $amount);
				}

				return $result;
			},
			/**
			 * Convert list of invoice items into a JSON object of the following form
			 * [{name: string, price: {amount: string, currency: string}, amount: int}].
			 *
			 * @param InvoiceItem[] $value
			 */
			function(mixed $value, string $newKey): string {
				\assert(\is_array($value)); // For PHPStan.

				return Json::encode(array_values($value));
			}
		);

		return $conventions;
	}
}
