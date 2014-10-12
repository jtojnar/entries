<?php

namespace App\Model;

use Nette\Diagnostics\Debugger;


class BaseModel extends \Nette\Object {
	/** @var \Nette\Database\Context @inject */
	protected $db;

	const TABLE_COUNTRY = 'country';
	const TABLE_TEAM = 'team';
	const TABLE_PERSON = 'person';
	const TABLE_EVENT = 'event';

	public function __construct(\Nette\Database\Context $db) {
		$this->db = $db;
	}
}
