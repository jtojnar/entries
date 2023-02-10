<?php

declare(strict_types=1);

namespace App\Model\Orm;

use Nextras\Orm\Mapper\Dbal\Conventions\Conventions;
use Nextras\Orm\Mapper\Dbal\Conventions\IConventions;
use Nextras\Orm\Mapper\Mapper;

class BaseMapper extends Mapper {
	protected function createConventions(): IConventions {
		/** @var Conventions */
		$conventions = parent::createConventions();
		$conventions->manyHasManyStorageNamePattern = '%s_%s';

		return $conventions;
	}
}
