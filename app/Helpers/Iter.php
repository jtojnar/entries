<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2019 Ihor Burlachenko
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Helpers;

final class Iter {
	/**
	 * @template T
	 *
	 * @param iterable<T> $iterable
	 * @param callable(T): bool $predicate
	 */
	public static function all(iterable $iterable, callable $predicate): bool {
		foreach ($iterable as $value) {
			if (!$predicate($value)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @template T
	 *
	 * @param iterable<T> $iterable
	 * @param callable(T): bool $predicate
	 */
	public static function any(iterable $iterable, callable $predicate): bool {
		foreach ($iterable as $value) {
			if ($predicate($value)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param iterable<array-key, iterable<mixed>> $inputs
	 *
	 * @return array<array<array-key, mixed>>
	 */
	public static function cartesianProduct(iterable $inputs): array {
		$result = [[]];
		foreach ($inputs as $key => $values) {
			$newResult = [];
			foreach ($result as $vector) {
				foreach ($values as $value) {
					$newResult[] = [...$vector, $key => $value];
				}
			}

			$result = $newResult;
		}

		return $result;
	}

	/**
	 * @template T
	 *
	 * @param iterable<T> $iterable
	 *
	 * @return ?T
	 */
	public static function last(iterable $iterable): mixed {
		$value = null;
		foreach ($iterable as $value) {
		}

		return $value;
	}

	/**
	 * @template K
	 * @template A
	 * @template B
	 *
	 * @param callable(A): B $mapper
	 * @param iterable<K, A> $iterable
	 *
	 * @return iterable<K, B>
	 */
	public static function map(callable $mapper, iterable $iterable): iterable {
		foreach ($iterable as $key => $value) {
			yield $key => $mapper($value);
		}
	}

	/**
	 * @template T
	 * @template Carry
	 *
	 * @param iterable<T> $iterable
	 * @param callable(Carry, T): Carry $reducer
	 * @param Carry $initial
	 *
	 * @return Carry
	 */
	public static function reduce(iterable $iterable, callable $reducer, mixed $initial = null): mixed {
		$carry = $initial;
		foreach ($iterable as $value) {
			$carry = $reducer($carry, $value);
		}

		return $carry;
	}
}
