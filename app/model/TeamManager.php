<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\AuthenticationException;
use Nette\Security\Identity;
use Nette\Security\Passwords;
use Nextras\Orm\Entity\ToArrayConverter;

final class TeamManager implements Nette\Security\IAuthenticator {
	use Nette\SmartObject;

	/** @var TeamRepository */
	private $teams;

	/** @var string administrator password */
	private $password;

	public function __construct(TeamRepository $teams, string $password) {
		$this->teams = $teams;
		$this->password = $password;
	}

	/**
	 * Performs an authentication.
	 *
	 * @param array $credentials
	 *
	 * @throws AuthenticationException
	 *
	 * @return Identity
	 */
	public function authenticate(array $credentials): Identity {
		[$teamId, $password] = $credentials;

		if ($teamId === 'admin' && $password === $this->password) {
			return new Identity('admin', 'admin', ['status' => 'admin']);
		}

		$team = $this->teams->getById($teamId);

		if (!$team) {
			throw new AuthenticationException('The ID of the team is incorrect.', self::IDENTITY_NOT_FOUND);
		} elseif (!Passwords::verify($password, $team->password)) {
			throw new AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
		} elseif (Passwords::needsRehash($team->password)) {
			$team->password = Passwords::hash($password);
			$this->teams->persistAndFlush($team);
		}

		$arr = ToArrayConverter::toArray($team);
		unset($arr['persons']);
		unset($arr['password']);
		unset($arr['invoices']);
		unset($arr['lastInvoice']);

		return new Identity($team->id, 'user', $arr);
	}
}
