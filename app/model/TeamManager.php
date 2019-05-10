<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\AuthenticationException;
use Nette\Security\Identity;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nextras\Orm\Entity\ToArrayConverter;

final class TeamManager implements Nette\Security\IAuthenticator {
	use Nette\SmartObject;

	/** @var TeamRepository */
	private $teams;

	/** @var string administrator password */
	private $adminPassword;

	/** @var Passwords */
	private $passwords;

	public function __construct(string $adminPassword, TeamRepository $teams, Passwords $passwords) {
		$this->teams = $teams;
		$this->adminPassword = $adminPassword;
		$this->passwords = $passwords;
	}

	/**
	 * Performs an authentication.
	 *
	 * @param array $credentials
	 *
	 * @throws AuthenticationException
	 *
	 * @return IIdentity
	 */
	public function authenticate(array $credentials): IIdentity {
		[$teamId, $password] = $credentials;

		if ($teamId === 'admin' && $password === $this->adminPassword) {
			return new Identity('admin', 'admin', ['status' => 'admin']);
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

		$arr = ToArrayConverter::toArray($team);
		unset($arr['persons']);
		unset($arr['password']);
		unset($arr['invoices']);
		unset($arr['lastInvoice']);

		return new Identity($team->id, 'user', $arr);
	}
}
