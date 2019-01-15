<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Utils\Json;

class InvoiceMapper extends BaseMapper {
	protected function createStorageReflection() {
		$reflection = parent::createStorageReflection();

		$reflection->setMapping(
			'items',
			$reflection->convertEntityToStorageKey('items'),
			function($value) {
				return Json::decode($value, Json::FORCE_ARRAY);
			},
			function($value) {
				return Json::encode($value);
			}
		);

		return $reflection;
	}
}
