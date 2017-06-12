<?php

namespace App\Model;

use Nette;

class CategoryData {
	use Nette\SmartObject;

	/** @var array */
	private $categoryData;

	public function __construct(array $categoryData) {
		$this->categoryData = $categoryData;
	}

	public function getCategoryData() {
		return $this->categoryData;
	}
}
