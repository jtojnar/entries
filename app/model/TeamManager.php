<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\AuthenticationException;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

final class TeamManager implements Nette\Security\IAuthenticator {
	use Nette\SmartObject;

	public function __construct(
		/** @var string administrator password */
		private string $adminPassword,
		private TeamRepository $teams,
		private Passwords $passwords,
	) {
	}

	/**
	 * Performs an authentication.
	 *
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials): IIdentity {
		[$teamId, $password] = $credentials;

		if ($teamId === 'admin') {
			if ($password === $this->adminPassword) {
				return new SimpleIdentity('admin', 'admin');
			} else {
				throw new AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
			}
		}

		$team = $this->teams->getById($teamId);

		if (!$team) {
			throw new AuthenticationException('The ID of the team is incorrect.', self::IDENTITY_NOT_FOUND);
		} elseif (!$this->passwords->verify($password, $team->password)) {
			throw new AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
		} elseif ($this->passwords->needsRehash($team->password)) {
			$team->password = $this->passwords->hash($password);
			$this->teams->persistAndFlush($team);
		}

		return $this->createUserIdentity($team);
	}

	public function createUserIdentity(Team $team): SimpleIdentity {
		$data = [
			'id' => $team->id,
		];

		return new SimpleIdentity($team->id, 'user', $data);
	}
}
