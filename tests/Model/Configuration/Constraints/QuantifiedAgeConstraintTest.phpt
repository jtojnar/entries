<?php

declare(strict_types=1);

namespace App\Tests\Model\Configuration\Constraints;

use App\Forms\TeamFormPersonValues;
use App\Model\Configuration\Constraints\ComparisonOperator;
use App\Model\Configuration\Constraints\EqualityOperator;
use App\Model\Configuration\Constraints\QuantifiedAgeConstraint;
use App\Model\Configuration\Constraints\Quantifier;
use DateTimeImmutable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

class QuantifiedAgeConstraintTest extends TestCase {
	public function testAllYounger(): void {
		$constraint = new QuantifiedAgeConstraint(
			quantifier: Quantifier::All,
			operator: ComparisonOperator::LessThan,
			targetAge: 18,
			eventDate: new DateTimeImmutable('2020-06-17'),
		);

		Assert::true(
			$constraint->admits([
			]),
			'Empty team is valid.',
		);

		Assert::true(
			$constraint->admits([
				self::mkPerson(
					// Birthday one day after event, >17 yo.
					new DateTimeImmutable('2002-06-18'),
				),
			]),
			'Team with a younger member is valid.',
		);

		Assert::false(
			$constraint->admits([
				self::mkPerson(
					// Birthday one day before event, >18 yo.
					new DateTimeImmutable('2002-06-16'),
				),
			]),
			'Team with an older member is not valid.',
		);

		Assert::false(
			$constraint->admits([
				self::mkPerson(
					// Birthday on event date, 18 yo.
					new DateTimeImmutable('2002-06-17'),
				),
			]),
			'Team with a member on boundary is not valid.',
		);
	}

	public function testAllEqual(): void {
		$constraint = new QuantifiedAgeConstraint(
			quantifier: Quantifier::All,
			operator: EqualityOperator::Equal,
			targetAge: 18,
			eventDate: new DateTimeImmutable('2020-06-17'),
		);

		Assert::true(
			$constraint->admits([
			]),
			'Empty team is valid.',
		);

		Assert::true(
			$constraint->admits([
				self::mkPerson(
					// Birthday on event date, 18 yo.
					new DateTimeImmutable('2002-06-17'),
				),
				self::mkPerson(
					// Birthday one day before event, >18 yo.
					new DateTimeImmutable('2002-06-16'),
				),
			]),
			'Team with only 18-yo’s is valid.',
		);

		Assert::false(
			$constraint->admits([
				self::mkPerson(
					// Birthday on event date, 18 yo.
					new DateTimeImmutable('2002-06-17'),
				),
				self::mkPerson(
					// Born on event date.
					new DateTimeImmutable('2020-06-17'),
				),
			]),
			'Team with a younger member is not valid.',
		);

		Assert::false(
			$constraint->admits([
				self::mkPerson(
					// Birthday on event date, 19 yo.
					new DateTimeImmutable('2001-06-17'),
				),
			]),
			'Team with an older member is not valid.',
		);
	}

	private static function mkPerson(DateTimeImmutable $birth): TeamFormPersonValues {
		return new TeamFormPersonValues(
			firstname: 'John',
			lastname: 'Doe',
			gender: 'male',
			email: '',
			birth: $birth,
		);
	}
}

(new QuantifiedAgeConstraintTest())->run();
