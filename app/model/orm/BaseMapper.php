<?php

namespace App\Model;

use Nextras\Orm\Mapper\Mapper;

class BaseMapper extends Mapper {
	public function getTableName() {
		return $this->repository->model->prefix . parent::getTableName();
	}

	protected function createStorageReflection() {
		$reflection = parent::createStorageReflection();
		$reflection->manyHasManyStorageNamePattern = '%s_%s';
		return $reflection;
	}
}
