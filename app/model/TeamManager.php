<?php

namespace App\Model;

use Nette;
use Nette\Utils\Strings;
use Nette\Security\Passwords;

class TeamManager extends BaseModel implements Nette\Security\IAuthenticator {
	const COLUMN_ID = 'id';
	const COLUMN_PASSWORD_HASH = 'password';

	/** @var Nette\Database\Context */
	private $database;

	private $password;


	public function __construct(Nette\Database\Context $database, $password) {
		$this->database = $database;
		$this->password = $password;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials) {
		list($teamid, $password) = $credentials;

		if($teamid === 'admin' && $password === $this->password) {
			return new Nette\Security\Identity('admin', 'admin', ['status' => 'admin']);
		}

		$row = $this->database->table(self::TABLE_TEAM)->where(self::COLUMN_ID, $teamid)->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('The ID of the team is incorrect.', self::IDENTITY_NOT_FOUND);
		} else if (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);

		} else if (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update(array(
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			));
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], 'user', $arr);
	}
}
