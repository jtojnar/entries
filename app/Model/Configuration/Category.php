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

final readonly class Category {
	private function __construct(
		public string $name,
		public ?int $minMembers,
		public ?int $maxMembers,
		/** @var array<Constraints\Constraint> */
		public array $constraints,
		public Fees $fees,
	) {
	}

	public static function from(
		string $key,
		mixed $category,
		Fees $parentFees,
		DateTimeInterface $eventDate,
	): self {
		$category = Helpers::ensureArray("categories.$key", $category);
		$fees = Fees::from("categories.$key", $category['fees'] ?? [], $parentFees);
		if ($fees->person === null) {
			throw new InvalidConfigurationException("No person fee set for category “{$key}”");
		}

		return new self(
			name: $key,
			fees: $fees,
			constraints: self::parseConstraints(
				"categories.$key.constraints",
				$category['constraints'] ?? [],
				$eventDate,
			),
			minMembers: $category['minMembers'] ?? null,
			maxMembers: $category['maxMembers'] ?? null,
		);
	}

	public const string CONSTRAINT_REGEX = '(^\s*(?P<quant>all|some)\((?P<key>age|gender)(?P<op>[<>]?=?)(?P<val>.+)\)$\s*)';
	public const string AGGREGATE_CONSTRAINT_REGEX = '(^\s*(?P<aggr>sum|min|max)\((?P<key>age)\)(?P<op>[<>]?=?)(?P<val>[0-9]+)$\s*)';

	public const array OP_LOOKUP = [
		'<' => Constraints\ComparisonOperator::LessThan,
		'<=' => Constraints\ComparisonOperator::LessThanOrEqual,
		'=' => Constraints\EqualityOperator::Equal,
		'>=' => Constraints\ComparisonOperator::MoreThanOrEqual,
		'>' => Constraints\ComparisonOperator::MoreThan,
	];

	public const array AGGR_LOOKUP = [
		'sum' => Constraints\AggregateFunction::Sum,
		'min' => Constraints\AggregateFunction::Min,
		'max' => Constraints\AggregateFunction::Max,
	];

	public const array KEY_SUPPORTED_OPS = [
		'age' => ['<', '<=', '=', '>=', '>'],
		'gender' => ['='],
	];

	public const array QUANT_LOOKUP = [
		'all' => Constraints\Quantifier::All,
		'some' => Constraints\Quantifier::Some,
	];

	/**
	 * @return Constraints\Constraint[]
	 */
	private static function parseConstraints(string $context, mixed $constraints, DateTimeInterface $eventDate): array {
		$constraints = Helpers::ensureStringListMaybe($context, $constraints);

		return array_map(function(string $constraint) use ($eventDate): Constraints\Constraint {
			if (preg_match(self::CONSTRAINT_REGEX, $constraint, $match) === 1) {
				['quant' => $quant, 'key' => $key, 'op' => $op, 'val' => $val] = $match;
				$quant = self::QUANT_LOOKUP[$quant];

				if (!\in_array($op, self::KEY_SUPPORTED_OPS[$key], true)) {
					throw new InvalidConfigurationException("Constraint “{$constraint}” is invalid: using ‘{$match['op']}’ with ‘{$match['key']}’ is not supported.");
				}
				$op = self::OP_LOOKUP[$op];

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
				}
			} elseif (preg_match(self::AGGREGATE_CONSTRAINT_REGEX, $constraint, $match) === 1) {
				['aggr' => $aggr, 'key' => $key, 'op' => $op, 'val' => $val] = $match;
				$aggr = self::AGGR_LOOKUP[$aggr];
				$op = self::OP_LOOKUP[$op];

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
