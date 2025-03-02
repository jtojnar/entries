<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2019 Ihor Burlachenko
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Tests\Helpers;

use App\Helpers\Iter;
use ArrayIterator;
use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

function testAll(): void {
	Assert::true(Iter::all([19, 20, 21], static fn(int $v): bool => $v > 18));
	Assert::false(Iter::all([19, 20, 21], static fn(int $v): bool => $v < 18));
}
testAll();

function testAny(): void {
	Assert::true(Iter::any([18, 19, 20], static fn(int $v): bool => $v === 18));
	Assert::false(Iter::any([19, 20, 21], static fn(int $v): bool => $v === 18));
}
testAny();

function testCartesianProduct(): void {
	$array1 = [1, 2, 3];
	$array2 = ['a', 'b'];

	Assert::same(
		[
			[1, 'a'],
			[1, 'b'],
			[2, 'a'],
			[2, 'b'],
			[3, 'a'],
			[3, 'b'],
		],
		Iter::cartesianProduct([
			$array1,
			$array2,
		]),
	);

	Assert::same(
		[
			['hello' => 1, 'world' => 'a'],
			['hello' => 1, 'world' => 'b'],
			['hello' => 2, 'world' => 'a'],
			['hello' => 2, 'world' => 'b'],
			['hello' => 3, 'world' => 'a'],
			['hello' => 3, 'world' => 'b'],
		],
		Iter::cartesianProduct(['hello' => $array1, 'world' => $array2]),
	);
}
testCartesianProduct();

function testFilterNull(): void {
	Assert::same(
		[1 => 1, 4 => 2, 6 => 3],
		iterator_to_array(Iter::filterNull(new ArrayIterator([null, 1, null, null, 2, null, 3, null]))),
	);
	Assert::same(
		['b' => 1, 'e' => 2, 'g' => 3],
		iterator_to_array(Iter::filterNull(['a' => null, 'b' => 1, 'c' => null, 'd' => null, 'e' => 2, 'f' => null, 'g' => 3, 'h' => null])),
	);
	Assert::same(
		[],
		iterator_to_array(Iter::filterNull([])),
	);
}
testFilterNull();

function testMap(): void {
	Assert::same(
		['A', 'B', 'C'],
		iterator_to_array(Iter::map(strtoupper(...), ['a', 'b', 'c'])),
	);
	Assert::same(
		[1, 4, 9],
		iterator_to_array(Iter::map(fn($v) => $v * $v, new ArrayIterator([1, 2, 3]))),
	);
	Assert::same(
		['a' => 0, 'b' => 1, 'c' => 2],
		iterator_to_array(Iter::map(abs(...), ['a' => 0, 'b' => -1, 'c' => 2])),
	);
	Assert::same(
		[],
		iterator_to_array(Iter::map(strtoupper(...), [])),
	);

	$range = function($min, $max) {
		for ($i = $min; $i <= $max; ++$i) {
			yield $i;
		}
	};
	Assert::same(
		[1, 4, 9],
		iterator_to_array(Iter::map(fn($v) => $v * $v, $range(1, 3))),
	);
}
testMap();

function testLast(): void {
	Assert::same(9, Iter::last([1, 2, 3, 4, 5, 6, 7, 8, 9]));
	Assert::same(9, Iter::last(new ArrayIterator([1, 2, 3, 4, 5, 6, 7, 8, 9])));
}
testLast();

function testReduce(): void {
	Assert::same(6, Iter::reduce([1, 2, 3], fn($a, $b) => $a + $b));
	Assert::same('abc', Iter::reduce(new ArrayIterator(['a', 'b', 'c']), fn($a, $b) => $a . $b, ''));
	Assert::same(64, Iter::reduce(['a' => 3, 'b' => 2, 'c' => 1], 'pow', 2));
	Assert::same(0, Iter::reduce([], fn($a, $b) => $a * $b, 0));
	Assert::same(1, Iter::reduce([], fn($a, $b) => $a * $b, 1));
}
testReduce();
