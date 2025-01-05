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

	public function testFlatCategoriesFeesWithCurrency(): void {
		$entries = Entries::from([
			'eventDate' => new DateTimeImmutable('2050-12-07'),
			'fees' => [
				'person' => 20,
				'currency' => 'GBP',
			],
			'categories' => [
				'overridden' => [
					'fees' => [
						'currency' => 'USD',
						'person' => 10,
					],
				],
				'inherited' => [
					'fees' => [
						'person' => 30,
					],
				],
			],
		]);
		Assert::equal(new Money(10_00, new Currency('USD')), $entries->categories->allCategories['overridden']->fees->person);
		Assert::equal(new Money(30_00, new Currency('GBP')), $entries->categories->allCategories['inherited']->fees->person);
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
}

(new BasicReadTest())->run();
