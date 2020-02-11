<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Utils\Json;
use Nextras\Orm\Mapper\Dbal\StorageReflection\StorageReflection;

class InvoiceMapper extends BaseMapper {
	protected function createStorageReflection(): StorageReflection {
		$reflection = parent::createStorageReflection();

		$reflection->setMapping(
			'items',
			$reflection->convertEntityToStorageKey('items'),
			function(string $value): array {
				return Json::decode($value, Json::FORCE_ARRAY);
			},
			function(array $value): string {
				return Json::encode($value);
			}
		);

		return $reflection;
	}
}
