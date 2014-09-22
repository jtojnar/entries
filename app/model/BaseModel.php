<?php

namespace App\Model;

use Nette\Diagnostics\Debugger;


class BaseModel extends \Nette\Object {
	/** @var \Nette\Database\Context @inject */
	protected $db;

	public function __construct(\Nette\Database\Context $db) {
		$this->db = $db;
	}
}
