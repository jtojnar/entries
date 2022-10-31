<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2019 Ihor Burlachenko
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Tests\Helpers;

use App\Helpers\Op;
use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

function testId(): void {
	Assert::same(1, Op::id(1));
	Assert::same('hello world', Op::id('hello world'));

	Assert::same(1, Op::id(1));
}
testId();

function testInt(): void {
	Assert::same(1, Op::int('1'));
	Assert::same(1, Op::int('1.0'));
}
testInt();

function testLt(): void {
	Assert::true(Op::lt(2, 3));
	Assert::false(Op::lt(2, 2));
	Assert::false(Op::lt(6, 3));
}
testLt();

function testLe(): void {
	Assert::true(Op::le(2, 3));
	Assert::true(Op::le(2, 2));
	Assert::false(Op::le(6, 3));
}
testLe();

function testGt(): void {
	Assert::true(Op::gt(3, 2));
	Assert::false(Op::gt(2, 2));
	Assert::false(Op::gt(3, 6));
}
testGt();

function testGe(): void {
	Assert::true(Op::ge(3, 2));
	Assert::true(Op::ge(2, 2));
	Assert::false(Op::ge(3, 6));
}
testGe();

function testIdnt(): void {
	Assert::true(Op::idnt(2, 2));
	Assert::false(Op::idnt(2, '2'));
	Assert::false(Op::idnt(3, 6));
}
testIdnt();
