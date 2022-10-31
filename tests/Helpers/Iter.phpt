<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2019 Ihor Burlachenko
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Tests\Helpers;

use App\Helpers\Iter;
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

function testLast(): void {
	Assert::same(9, Iter::last([1, 2, 3, 4, 5, 6, 7, 8, 9]));
	Assert::same(9, Iter::last(new \ArrayIterator([1, 2, 3, 4, 5, 6, 7, 8, 9])));
}
testLast();
