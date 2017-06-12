<?php

namespace App\Generator;

use Nette;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\PhpGenerator as Code;
use Nette\Utils\ArrayHash;

class CategoryData {
	use Nette\StaticClass;

	const CONSTRAINT_REGEX = '(^\s*(?P<quant>all|some)\((?P<op>age[<>]?=?|gender=)(?P<val>.+)\)$\s*)';

	const OP_LOOKUP = [
		'age<' => 'ageLt',
		'age<=' => 'ageLe',
		'age=' => 'ageEq',
		'age>=' => 'ageGe',
		'age>' => 'ageGt',
		'gender=' => 'genderEq',
	];

	const OBJ_LOOKUP = [
		'age<' => 'age',
		'age<=' => 'age',
		'age=' => 'age',
		'age>=' => 'age',
		'age>' => 'age',
		'gender=' => 'gender',
	];

	const QUANT_LOOKUP = [
		'all' => 'quantAll',
		'some' => 'quantSome',
	];

	public static function parseConstraints(array $category, array $config) {
		if (!isset($category['constraints'])) {
			return [];
		}

		return array_map(function(string $constraint) use ($config) {
			if (preg_match(self::CONSTRAINT_REGEX, $constraint, $match)) {
				$quant = self::class . '::' . self::QUANT_LOOKUP[$match['quant']];
				$op = self::class . '::' . self::OP_LOOKUP[$match['op']];
				$val = $match['val'];
				$data = self::OBJ_LOOKUP[$match['op']] === 'age' ? $config['eventDate'] : null;

				$closure = Nette\PhpGenerator\Closure::from(function(BaseControl $entry) {
				});
				$ret = Code\Helpers::formatArgs($quant . '(?, ?, ?, ?)', [
					new Code\PhpLiteral('$entry->getForm()'),
					$op,
					$val,
					$data,
				]);
				$closure->setBody('return ' . $ret . ';');

				if ($match['op'] === 'gender=') {
					$message = 'messages.team.error.gender_mismatch';
				} else {
					$message = 'messages.team.error.age_mismatch';
				}

				return [
					new Code\PhpLiteral($closure),
					$message
				];
			}

			throw new \Exception("Constraint “${constraint}” is invalid");
		}, $category['constraints']);
	}

	public static function ageLt(ArrayHash $person, $value, \DateTimeInterface $eventDate): bool {
		if (!isset($person['birth'])) {
			return true;
		}

		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age < (int) $value;
	}

	public static function ageLe(ArrayHash $person, $value, \DateTimeInterface $eventDate): bool {
		if (!isset($person['birth'])) {
			return true;
		}

		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age <= (int) $value;
	}

	public static function ageEq(ArrayHash $person, $value, \DateTimeInterface $eventDate): bool {
		if (!isset($person['birth'])) {
			return true;
		}

		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age === (int) $value;
	}

	public static function ageGe(ArrayHash $person, $value, \DateTimeInterface $eventDate): bool {
		if (!isset($person['birth'])) {
			return true;
		}

		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age >= (int) $value;
	}

	public static function ageGt(ArrayHash $person, $value, \DateTimeInterface $eventDate): bool {
		if (!isset($person['birth'])) {
			return true;
		}

		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age > (int) $value;
	}

	public static function genderEq(ArrayHash $person, $value, $data): bool {
		return $person['gender'] === $value;
	}

	public static function quantAll(Form $team, callable $fn, $value, $data = null): bool {
		foreach ($team['persons']->values as $person) {
			if (!$fn($person, $value, $data)) {
				return false;
			}
		}

		return true;
	}

	public static function quantSome(Form $team, callable $fn, $value, $data = null): bool {
		foreach ($team['persons']->values as $person) {
			if ($fn($person, $value, $data)) {
				return true;
			}
		}

		return false;
	}
}
