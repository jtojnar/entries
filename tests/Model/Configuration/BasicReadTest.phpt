<?php

declare(strict_types=1);

namespace App\Tests\Model\Configuration;

use App\Model\Configuration;
use App\Model\Configuration\Entries;
use App\Model\Configuration\InvalidConfigurationException;
use DateTimeImmutable;
use Money\Currency;
use Money\Money;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class BasicReadTest extends TestCase {
	public function testNoCategories(): void {
		Assert::exception(
			function(): void {
				$entries = Entries::from([
					'eventDate' => new DateTimeImmutable('2050-12-07'),
					'categories' => [],
				]);
			},
			InvalidConfigurationException::class,
			'No categories defined.',
		);
	}

	public function testFlatCategoriesNoFees(): void {
		Assert::exception(
			function(): void {
				$entries = Entries::from([
					'eventDate' => new DateTimeImmutable('2050-12-07'),
					'categories' => [
						'MO' => [],
						'WO' => [],
						'XO' => [],
					],
				]);
			},
			InvalidConfigurationException::class,
			'No person fee set for category “MO”',
		);
	}

	public function testFlatCategories(): void {
		$entries = Entries::from([
			'eventDate' => new DateTimeImmutable('2050-12-07'),
			'fees' => [
				'person' => 20,
			],
			'categories' => [
				'MO' => [],
				'WO' => [],
				'XO' => [],
			],
		]);
		Assert::same(3, \count($entries->categories->categoriesOrGroups));
		Assert::same(['MO', 'WO', 'XO'], array_map(static fn(Configuration\Category $cat) => $cat->name, $entries->categories->categoriesOrGroups));
		Assert::same(['MO', 'WO', 'XO'], array_keys($entries->categories->allCategories));
		Assert::same(false, $entries->categories->nested);
	}

	public function testNestedCategories(): void {
		$entries = Entries::from([
			'eventDate' => new DateTimeImmutable('2050-12-07'),
			'fees' => [
				'person' => 20,
			],
			'categories' => [
				'24' => [
					'label' => '24 hours',
					'categories' => [
						'MO' => [],
						'WO' => [],
						'XO' => [],
					],
				],
				'6' => [
					'label' => '6 hours',
					'categories' => [
						'MO6' => [],
						'WO6' => [],
						'XO6' => [],
					],
				],
			],
		]);
		Assert::same(2, \count($entries->categories->categoriesOrGroups));
		Assert::same(['24', '6'], array_map(static fn(Configuration\CategoryGroup $cat) => $cat->key, $entries->categories->categoriesOrGroups));
		Assert::same(['24 hours', '6 hours'], array_map(static fn(Configuration\CategoryGroup $cat) => $cat->label->message, $entries->categories->categoriesOrGroups));
		Assert::same(['MO', 'WO', 'XO', 'MO6', 'WO6', 'XO6'], array_keys($entries->categories->allCategories));
		Assert::same(true, $entries->categories->nested);
	}

	public function testFlatCategoriesFees(): void {
		$entries = Entries::from([
			'eventDate' => new DateTimeImmutable('2050-12-07'),
			'fees' => [
				'person' => 20,
			],
			'categories' => [
				'overridden' => [
					'fees' => [
						'person' => 10,
					],
				],
				'inherited' => [],
			],
		]);
		Assert::equal(new Money(10_00, new Currency('CZK')), $entries->categories->allCategories['overridden']->fees->person);
		Assert::equal(new Money(20_00, new Currency('CZK')), $entries->categories->allCategories['inherited']->fees->person);
	}

	public function testNestedCategoriesFees(): void {
		$entries = Entries::from([
			'eventDate' => new DateTimeImmutable('2050-12-07'),
			'fees' => [
				'person' => 20,
			],
			'categories' => [
				'overridden' => [
					'label' => 'Category group with fee',
					'fees' => [
						'person' => 30,
					],
					'categories' => [
						'o-overridden' => [
							'fees' => [
								'person' => 40,
							],
						],
						'o-inherited' => [],
					],
				],
				'inherited' => [
					'label' => 'Category group without fee',
					'categories' => [
						'i-overridden' => [
							'fees' => [
								'person' => 50,
							],
						],
						'i-inherited' => [],
					],
				],
			],
		]);
		Assert::equal(new Money(40_00, new Currency('CZK')), $entries->categories->allCategories['o-overridden']->fees->person);
		Assert::equal(new Money(30_00, new Currency('CZK')), $entries->categories->allCategories['o-inherited']->fees->person);
		Assert::equal(new Money(50_00, new Currency('CZK')), $entries->categories->allCategories['i-overridden']->fees->person);
		Assert::equal(new Money(20_00, new Currency('CZK')), $entries->categories->allCategories['i-inherited']->fees->person);
	}

	public function testFlatCategoriesAllTopLevelFields(): void {
		$entries = Entries::from([
			'supportedLocales' => ['en', 'cs', 'de'],
			'fees' => [
				'person' => '200',
				'currency' => 'PLN',
			],
			'eventDate' => new DateTimeImmutable('2024-12-15'),
			'minMembers' => 3,
			'fields' => [
				'person' => [],
				'team' => [],
			],
			'accountNumber' => '1325090010/3030',
			'initialMembers' => 4,
			'maxMembers' => 7,
			'allowPlaceholders' => true,
			'allowLateRegistrationsByEmail' => true,
			'recommendedCardCapacity' => 42,
			'categories' => [
				'all' => [],
			],
			'opening' => new DateTimeImmutable('2024-06-11'),
			'closing' => new DateTimeImmutable('2024-12-11'),
			'limits' => [
				'beds' => 37,
			],
			'invoiceModifier' => \App\Config\CustomInvoiceModifier::class,
			'inputModifier' => \App\Config\BcnMandatoryForCzechs::class,
		]);

		// supportedLocales not exposed.

		Assert::same(
			new Money('20000', new Currency('PLN')),
			$entries->fees->person,
			'person',
		);

		Assert::same(
			new DateTimeImmutable('2024-12-15'),
			$entries->eventDate,
			'eventDate',
		);

		Assert::same(
			3,
			$entries->minMembers,
			'minMembers',
		);

		Assert::same(
			'1325090010/3030',
			$entries->accountNumber,
			'accountNumber',
		);

		Assert::same(
			4,
			$entries->initialMembers,
			'initialMembers',
		);

		Assert::same(
			7,
			$entries->maxMembers,
			'maxMembers',
		);

		Assert::true($entries->allowPlaceholders, 'allowPlaceholders');

		Assert::true($entries->allowLateRegistrationsByEmail, 'allowLateRegistrationsByEmail');

		Assert::same(
			42,
			$entries->recommendedCardCapacity,
			'recommendedCardCapacity',
		);

		Assert::same(
			['all'],
			array_map(function($cat) {
				\assert($cat instanceof Configuration\Category, 'category');

				return $cat->name;
			}, $entries->categories->categoriesOrGroups),
			'categoriesOrGroups',
		);
		Assert::same(['all'], array_keys($entries->categories->allCategories), 'allCategories');
		Assert::false($entries->categories->nested, 'not nested');

		Assert::same(
			new DateTimeImmutable('2024-06-11'),
			$entries->opening,
			'opening',
		);

		Assert::same(
			new DateTimeImmutable('2024-12-11'),
			$entries->closing,
			'closing',
		);

		Assert::same(
			['beds' => 37],
			$entries->limits,
			'limits',
		);

		Assert::same(
			\App\Config\CustomInvoiceModifier::class,
			$entries->invoiceModifier,
			'invoiceModifier',
		);
		Assert::same(
			\App\Config\BcnMandatoryForCzechs::class,
			$entries->inputModifier,
			'inputModifier',
		);
	}
}

(new BasicReadTest())->run();
