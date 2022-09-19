<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Utils\Random;
use Nextras\Orm\Repository\Repository;

final class TokenRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Token::class];
	}

	public function createForTeam(Team $team): string {
		$key = Random::generate(32);
		$token = new Token();
		$token->hash = password_hash($key, \PASSWORD_DEFAULT);
		$token->team = $team;

		$this->persist($token);

		return $token->id . ':' . $key;
	}

	public function getAllowedTeam(string $grant): ?Team {
		$parts = explode(':', $grant, 2);

		if (\count($parts) !== 2) {
			return null;
		}

		[$id, $key] = $parts;

		$token = $this->getById($id);

		if ($token === null || !password_verify($key, $token->hash)) {
			return null;
		}

		return $token->team;
	}
}
