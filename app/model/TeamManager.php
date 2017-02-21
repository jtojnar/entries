<?php

namespace App\Model;

use Nette;
use Nette\Utils\Strings;
use Nette\Security\Passwords;

class TeamManager implements Nette\Security\IAuthenticator {
	/** @var TeamRepository */
	private $teams;

	private $password;

	public function __construct(TeamRepository $teams, $password) {
		$this->teams = $teams;
		$this->password = $password;
	}

	/**
	 * Performs an authentication.
	 *
	 * @throws Nette\Security\AuthenticationException
	 *
	 * @return Nette\Security\Identity
	 */
	public function authenticate(array $credentials) {
		list($teamid, $password) = $credentials;

		if ($teamid === 'admin' && $password === $this->password) {
			return new Nette\Security\Identity('admin', 'admin', ['status' => 'admin']);
		}

		$team = $this->teams->getById($teamid);

		if (!$team) {
			throw new Nette\Security\AuthenticationException('The ID of the team is incorrect.', self::IDENTITY_NOT_FOUND);
		} elseif (!Passwords::verify($password, $team->password)) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
		} elseif (Passwords::needsRehash($team->password)) {
			$team->password = Passwords::hash($password);
			$this->teams->persistAndFlush($team);
		}

		$arr = $team->toArray();
		unset($arr['persons']);
		unset($arr['password']);

		return new Nette\Security\Identity($team->id, 'user', $arr);
	}
}
