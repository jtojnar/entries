<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration;

use App\Locale\Translated;
use App\Model\Configuration\Fields\Field;
use Contributte\Translation\Wrappers\NotTranslate;
use Money\Money;
use RuntimeException;

/**
 * Holds information about the event pertaining to registration.
 */
final class Helpers {
	public static function checkAllLocalesPresent(string $context, array $translated, array $allLocales): void {
		$translations = array_keys($translated);
		foreach ($allLocales as $locale) {
			if (!\in_array($locale, $translations, true)) {
				throw new RuntimeException("Missing translation {$locale} for {$context}.");
			}
		}
	}

	public static function parseTranslatable(string $context, mixed $translatable, array $allLocales): Translated|NotTranslate {
		if (\is_array($translatable)) {
			self::checkAllLocalesPresent($context, $translatable, $allLocales);

			return new class($translatable) implements Translated {
				public function __construct(
					private readonly array $translatable,
				) {
				}

				public function getMessage(string $locale): string {
					return $this->translatable[$locale];
				}
			};
		} elseif (\is_string($translatable)) {
			return new NotTranslate($translatable);
		}

		throw new RuntimeException("{$context} must be an array or string.");
	}

	/**
	 * @param array<string, mixed> $field
	 * @param ?string $fallback
	 */
	public static function parseLabel(string $context, array $field, array $allLocales, ?string $fallback = null): Translated|NotTranslate|string {
		if (isset($field['label'])) {
			$label = $field['label'];

			return self::parseTranslatable("label of {$context}", $label, $allLocales);
		} elseif ($fallback !== null) {
			return $fallback;
		}

		throw new \RuntimeException("{$context} lacks a label");
	}

	/**
	 * @template U
	 * @template V
	 *
	 * @param ?class-string<U> $class
	 * @param class-string<V> $expected
	 *
	 * @return ?class-string<V>
	 */
	public static function ensureSubclassOf(?string $class, string $expected): ?string {
		if ($class === null) {
			return $class;
		}

		if (!class_exists($class)) {
			throw new RuntimeException("Class “{$class}” does not exist.");
		}

		if (!is_subclass_of($class, $expected)) {
			throw new RuntimeException("Class “{$class}” is not a subclass of “{$expected}”.");
		}

		return $class;
	}

	/**
	 * @return array<string, array>
	 */
	public static function ensureFields(string $context, mixed $fields): array {
		if (!\is_array($fields)) {
			throw new RuntimeException("Expected array for {$context} fields.");
		}

		foreach ($fields as $key => $field) {
			if (!\is_string($key)) {
				throw new RuntimeException("Keys of {$context} fields should be strings, “{$key}” given.");
			}
			if (!\is_array($field)) {
				throw new RuntimeException("Expected array for {$context} field “{$key}”.");
			}
		}

		return $fields;
	}

	public static function ensureBool(string $context, mixed $value): bool {
		if (!\is_bool($value)) {
			throw new RuntimeException("Expected boolean for {$context}.");
		}

		return $value;
	}

	public static function ensureBoolMaybe(string $context, mixed $value): ?bool {
		if ($value === null) {
			return $value;
		}

		if (!\is_bool($value)) {
			throw new RuntimeException("Expected boolean for {$context}.");
		}

		return $value;
	}

	public static function ensureIntMaybe(string $context, mixed $value): ?int {
		if ($value === null) {
			return $value;
		}

		if (!\is_int($value)) {
			throw new RuntimeException("Expected integer for {$context}.");
		}

		return $value;
	}

	public static function ensureStringMaybe(string $context, mixed $value): ?string {
		if ($value === null) {
			return $value;
		}

		if (!\is_string($value)) {
			throw new RuntimeException("Expected string for {$context}.");
		}

		return $value;
	}

	/**
	 * @return array<string>|null
	 */
	public static function ensureStringListMaybe(string $context, mixed $value): ?array {
		if ($value === null) {
			return $value;
		}

		if (!\is_array($value)) {
			throw new RuntimeException("Expected list for {$context}.");
		}

		foreach ($value as $item) {
			if (!\is_string($item)) {
				throw new RuntimeException("Expected string for item of {$context}.");
			}
		}

		return $value;
	}

	/**
	 * @return array<string, int>
	 */
	public static function ensureLimits(mixed $limits): array {
		if (!\is_array($limits)) {
			throw new RuntimeException('Expected list for limits.');
		}

		foreach ($limits as $key => $limit) {
			if (!\is_string($key)) {
				throw new RuntimeException("Limit name must be string, “{$key}” given.");
			}

			if (!\is_int($limit) || $limit < 0) {
				throw new RuntimeException("Limit  “{$key}” must be a natural number, {$limit} given.");
			}
		}

		return $limits;
	}

	public static function makeFee(string $context, mixed $fee, Fees $fees, ?Money $fallbackFee = null): ?Money {
		if ($fee === null) {
			return $fallbackFee;
		}

		if (!\is_int($fee)) {
			throw new RuntimeException("{$context} must be an integer.");
		}

		return new Money($fee * 100, $fees->currency);
	}

	/**
	 * @param array<string> $allLocales
	 *
	 * @return array<string, Fields\Item>
	 */
	public static function makeItems(
		string $context,
		mixed $items,
		?Money $fallbackFee,
		array $allLocales,
		Fees $fees,
		bool $disabled,
		?string $limitName,
	): array {
		if (!\is_array($items)) {
			throw new RuntimeException("{$context} must be a list.");
		}

		return array_combine(
			array_keys($items),
			array_map(
				fn(string $name, mixed $item) => new Fields\Item(
					name: $name,
					label: self::parseLabel("{$name} field", $item, $allLocales),
					disabled: self::ensureBool("disabled of {$name} inside {$context}", $item['disabled'] ?? $disabled),
					limitName: self::ensureStringMaybe("limit of {$name} inside {$context}", $item['limit'] ?? $limitName),
					default: self::ensureBoolMaybe("default of {$name} inside {$context}", $item['default'] ?? null),
					fee: self::makeFee(
						"fee of {$name} inside {$context}",
						$item['fee'] ?? null,
						$fees,
						$fallbackFee,
					),
				),
				array_keys($items),
				$items,
			),
		);
	}

	public static function makeField(string $name, array $field, array $allLocales, Fees $fees): Field {
		foreach ($field as $key => $property) {
			if (!\is_string($key)) {
				throw new RuntimeException("Keys of {$name} field should be strings, “{$key}” given.");
			}
		}

		/** @var array<string, mixed> $field */
		$field = $field;
		$type = self::ensureStringMaybe("type of {$name} filed", $field['type'] ?? 'text');

		return match ($type) {
			'country' => new Fields\CountryField(
				name: $name,
				label: self::parseLabel("{$name} field", $field, $allLocales, 'messages.team.person.country.label'),
				public: self::ensureBool("public of {$name} field", $field['public'] ?? false),
				disabled: self::ensureBool("disabled of {$name} field", $field['disabled'] ?? false),
				default: self::ensureIntMaybe("default of {$name} field", $field['default'] ?? null),
				description: isset($field['description']) ? self::parseTranslatable("description of {$name} field", $field['description'], $allLocales) : null,
				applicableCategories: self::ensureStringListMaybe("applicableCategories of {$name}", $field['applicableCategories'] ?? null),
			),
			'phone' => new Fields\PhoneField(
				name: $name,
				label: self::parseLabel("{$name} field", $field, $allLocales, 'messages.team.phone.label'),
				public: self::ensureBool("public of {$name} field", $field['public'] ?? false),
				disabled: self::ensureBool("disabled of {$name} field", $field['disabled'] ?? false),
				description: isset($field['description']) ? self::parseTranslatable("description of {$name} field", $field['description'], $allLocales) : null,
				applicableCategories: self::ensureStringListMaybe("applicableCategories of {$name}", $field['applicableCategories'] ?? null),
			),
			'sportident' => new Fields\SportidentField(
				name: $name,
				label: self::parseLabel("{$name} field", $field, $allLocales, 'messages.team.person.si.label'),
				public: self::ensureBool("public of {$name} field", $field['public'] ?? false),
				disabled: self::ensureBool("disabled of {$name} field", $field['disabled'] ?? false),
				description: isset($field['description']) ? self::parseTranslatable("description of {$name} field", $field['description'], $allLocales) : null,
				applicableCategories: self::ensureStringListMaybe("applicableCategories of {$name}", $field['applicableCategories'] ?? null),
				fee: self::makeFee("fee of {$name} field", $field['fee'] ?? null, $fees),
			),
			'enum' => (function() use ($name, $field, $allLocales, $fees): Field {
				$fee = self::makeFee("fee of {$name} field", $field['fee'] ?? null, $fees);
				$disabled = self::ensureBool("disabled of {$name} field", $field['disabled'] ?? false);
				$limitName = self::ensureStringMaybe("limit of {$name} field", $field['limit'] ?? null);

				return new Fields\EnumField(
					name: $name,
					label: self::parseLabel("{$name} field", $field, $allLocales),
					public: self::ensureBool("public of {$name} field", $field['public'] ?? false),
					disabled: $disabled,
					limitName: $limitName,
					fee: $fee,
					options: self::makeItems(
						context: "options of {$name} field",
						items: $field['options'] ?? [],
						fallbackFee: $fee,
						allLocales: $allLocales,
						fees: $fees,
						disabled: $disabled,
						limitName: $limitName,
					),
					description: isset($field['description']) ? self::parseTranslatable("description of {$name} field", $field['description'], $allLocales) : null,
					applicableCategories: self::ensureStringListMaybe("applicableCategories of {$name}", $field['applicableCategories'] ?? null),
				);
			})(),
			'checkbox' => new Fields\CheckboxField(
				name: $name,
				label: self::parseLabel("{$name} field", $field, $allLocales),
				public: self::ensureBool("public of {$name} field", $field['public'] ?? false),
				disabled: self::ensureBool("disabled of {$name} field", $field['disabled'] ?? false),
				limitName: self::ensureStringMaybe("limit of {$name} field", $field['limit'] ?? null),
				default: self::ensureBoolMaybe("default of {$name} field", $field['default'] ?? null),
				description: isset($field['description']) ? self::parseTranslatable("description of {$name} field", $field['description'], $allLocales) : null,
				applicableCategories: self::ensureStringListMaybe("applicableCategories of {$name}", $field['applicableCategories'] ?? null),
				fee: self::makeFee(
					"fee of {$name} field",
					$field['fee'] ?? null,
					$fees,
				),
			),
			'checkboxlist' => (function() use ($name, $field, $allLocales, $fees): Field {
				$fee = self::makeFee("fee of {$name} field", $field['fee'] ?? null, $fees);
				$disabled = self::ensureBool("disabled of {$name} field", $field['disabled'] ?? false);
				$limitName = self::ensureStringMaybe("limit of {$name} field", $field['limit'] ?? null);

				return new Fields\CheckboxlistField(
					name: $name,
					label: self::parseLabel("{$name} field", $field, $allLocales),
					public: self::ensureBool("public of {$name} field", $field['public'] ?? false),
					disabled: $disabled,
					limitName: $limitName,
					fee: $fee,
					items: self::makeItems(
						context: "items of {$name} field",
						items: $field['items'] ?? [],
						fallbackFee: $fee,
						allLocales: $allLocales,
						fees: $fees,
						disabled: $disabled,
						limitName: $limitName,
					),
					description: isset($field['description']) ? self::parseTranslatable("description of {$name} field", $field['description'], $allLocales) : null,
					applicableCategories: self::ensureStringListMaybe("applicableCategories of {$name}", $field['applicableCategories'] ?? null),
				);
			})(),
			'text' => new Fields\TextField(
				name: $name,
				label: self::parseLabel("{$name} field", $field, $allLocales),
				public: self::ensureBool("public of {$name} field", $field['public'] ?? false),
				disabled: self::ensureBool("disabled of {$name} field", $field['disabled'] ?? false),
				description: isset($field['description']) ? self::parseTranslatable("description of {$name} field", $field['description'], $allLocales) : null,
				applicableCategories: self::ensureStringListMaybe("applicableCategories of {$name}", $field['applicableCategories'] ?? null),
			),
			default => throw new RuntimeException("Field “{$name}” has an unknown type “{$type}”."),
		};
	}
}
