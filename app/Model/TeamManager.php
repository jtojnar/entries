<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Orm\Team\Team;
use App\Model\Orm\Team\TeamRepository;
use Nette;
use Nette\Security\AuthenticationException;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;
use Override;

final readonly class TeamManager implements Nette\Security\Authenticator {
	public const int EntryWithdrawn = 317806432;

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
	#[Override]
	public function authenticate(string $teamId, string $password): IIdentity {
		if ($teamId === 'admin') {
			if ($password === $this->adminPassword) {
				return new SimpleIdentity('admin', 'admin');
			} else {
				throw new AuthenticationException('The password is incorrect.', self::InvalidCredential);
			}
		}

		$team = $this->teams->getById($teamId);

		if ($team === null) {
			throw new AuthenticationException('The ID of the team is incorrect.', self::IdentityNotFound);
		} elseif (!$this->passwords->verify($password, $team->password)) {
			throw new AuthenticationException('The password is incorrect.', self::InvalidCredential);
		} elseif ($team->status === Team::STATUS_WITHDRAWN) {
			throw new AuthenticationException('The entry has been withdrawn.', self::EntryWithdrawn);
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
