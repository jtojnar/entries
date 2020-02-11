<?php

declare(strict_types=1);

namespace App\Model;

use Nextras\Orm\Mapper\Dbal\StorageReflection\StorageReflection;
use Nextras\Orm\Mapper\Mapper;

class BaseMapper extends Mapper {
	protected function createStorageReflection(): StorageReflection {
		$reflection = parent::createStorageReflection();
		$reflection->manyHasManyStorageNamePattern = '%s_%s';

		return $reflection;
	}
}
