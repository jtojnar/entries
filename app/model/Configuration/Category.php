<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration;

use DateTimeInterface;

/**
 * @template T of 'age'|'gender'
 *
 * @param T $type
 *
 * @return (T is 'age' ? int : Constraints\Sex)
 */
function parse_value(string $type, string $value): int|Constraints\Sex {
	return match ($type) {
		'age' => (int) $value,
		'gender' => Constraints\Sex::from($value),
	};
}

final class Category {
	private function __construct(
		public readonly string $name,
		public readonly ?int $minMembers,
		public readonly ?int $maxMembers,
		/** @var array<Constraints\Constraint> */
		public readonly array $constraints,
		public readonly Fees $fees,
	) {
	}

	public static function from(
		string $key,
		array $category,
		Fees $parentFees,
		DateTimeInterface $eventDate
	): self {
		$fees = Fees::from($category['fees'] ?? [], $parentFees);
		if ($fees->person === null) {
			throw new InvalidConfigurationException("No person fee set for category “{$key}”");
		}

		return new self(
			name: $key,
			fees: $fees,
			constraints: self::parseConstraints(
				$category['constraints'] ?? [],
				$eventDate,
			),
			minMembers: $category['minMembers'] ?? null,
			maxMembers: $category['maxMembers'] ?? null,
		);
	}

	public const CONSTRAINT_REGEX = '(^\s*(?P<quant>all|some)\((?P<key>age|gender)(?P<op>[<>]?=?)(?P<val>.+)\)$\s*)';
	public const AGGREGATE_CONSTRAINT_REGEX = '(^\s*(?P<aggr>(sum|min|max))\((?P<key>age)\)(?P<op>[<>]?=?)(?P<val>[0-9]+)$\s*)';

	public const OP_LOOKUP = [
		'<' => Constraints\ComparisonOperator::LessThan,
		'<=' => Constraints\ComparisonOperator::LessThanOrEqual,
		'=' => Constraints\EqualityOperator::Equal,
		'>=' => Constraints\ComparisonOperator::MoreThanOrEqual,
		'>' => Constraints\ComparisonOperator::MoreThan,
	];

	public const AGGR_LOOKUP = [
		'sum' => Constraints\AggregateFunction::Sum,
		'min' => Constraints\AggregateFunction::Min,
		'max' => Constraints\AggregateFunction::Max,
	];

	public const KEY_SUPPORTED_OPS = [
		'age' => ['<', '<=', '=', '>=', '>'],
		'gender' => ['='],
	];

	public const QUANT_LOOKUP = [
		'all' => Constraints\Quantifier::All,
		'some' => Constraints\Quantifier::Some,
	];

	private static function parseConstraints(array $constraints, DateTimeInterface $eventDate): array {
		return array_map(function(string $constraint) use ($eventDate): Constraints\Constraint {
			if (preg_match(self::CONSTRAINT_REGEX, $constraint, $match)) {
				['quant' => $quant, 'key' => $key, 'op' => $op, 'val' => $val] = $match;
				$quant = self::QUANT_LOOKUP[$quant];

				if (!\in_array($op, self::KEY_SUPPORTED_OPS[$key], true)) {
					throw new InvalidConfigurationException("Constraint “{$constraint}” is invalid: using ‘${match['op']}’ with ‘${match['key']}’ is not supported.");
				}
				$op = self::OP_LOOKUP[$op];

				\assert(\in_array($key, ['age', 'gender'], true)); // For PHPStan
				$comparedValue = parse_value($key, $val);

				if ($key === 'age') {
					\assert(\is_int($comparedValue)); // For PHPStan

					return new Constraints\QuantifiedAgeConstraint(
						$quant,
						$op,
						$comparedValue,
						$eventDate,
					);
				} elseif ($key === 'gender') {
					\assert($op instanceof Constraints\EqualityOperator); // For PHPStan
					\assert($comparedValue instanceof Constraints\Sex); // For PHPStan

					return new Constraints\QuantifiedSexConstraint(
						$quant,
						$op,
						$comparedValue,
					);
				} else {
					throw new \PHPStan\ShouldNotHappenException();
				}
			} elseif (preg_match(self::AGGREGATE_CONSTRAINT_REGEX, $constraint, $match)) {
				['aggr' => $aggr, 'key' => $key, 'op' => $op, 'val' => $val] = $match;
				$aggr = self::AGGR_LOOKUP[$aggr];
				$op = self::OP_LOOKUP[$op];

				\assert(\in_array($key, ['age'], true)); // For PHPStan
				$comparedValue = parse_value($key, $val);

				return match ($key) {
					'age' => new Constraints\AggregateAgeConstraint(
						$aggr,
						$op,
						$comparedValue,
						$eventDate,
					),
				};
			}

			throw new InvalidConfigurationException("Constraint “{$constraint}” is invalid");
		}, $constraints);
	}
}
