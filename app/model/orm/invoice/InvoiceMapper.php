<?php

declare(strict_types=1);

namespace App\Model;

use Money\Currency;
use Money\Money;
use Nette\Utils\Json;
use Nextras\Orm\Mapper\Dbal\Conventions\IConventions;

class InvoiceMapper extends BaseMapper {
	protected function createConventions(): IConventions {
		$conventions = parent::createConventions();

		$conventions->setMapping(
			'items',
			$conventions->convertEntityToStorageKey('items'),
			/**
			 * Convert JSON object of the following form into a list of invoice items
			 * [{name: string, price: {amount: string, currency: string}, amount: int}].
			 *
			 * @return InvoiceItem[]
			 */
			function(string $value): array {
				$items = Json::decode($value, Json::FORCE_ARRAY);

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
			function(array $value): string {
				return Json::encode(array_values($value));
			}
		);

		return $conventions;
	}
}
