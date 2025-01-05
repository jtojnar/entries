<?php

// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2022 Jan Tojnar

declare(strict_types=1);

namespace App\Model\Configuration;

use App\Model\Configuration\Fields\Field;
use App\Model\InputModifier;
use App\Model\InvoiceModifier;
use DateTimeImmutable;

/**
 * Holds information about the event pertaining to registration.
 */
final class Entries {
	private function __construct(
		public readonly int $initialMembers,
		public readonly int $minMembers,
		public readonly ?int $maxMembers,
		public readonly bool $allowPlaceholders,
		public readonly DateTimeImmutable $eventDate,
		public readonly bool $allowLateRegistrationsByEmail,
		public readonly int $recommendedCardCapacity,
		public readonly Fees $fees,
		public readonly CategoryData $categories,
		/** @var array<string, Field> */
		public readonly array $personFields,
		/** @var array<string, Field> */
		public readonly array $teamFields,
		public readonly ?DateTimeImmutable $opening,
		public readonly ?DateTimeImmutable $closing,
		/** @var array<string, int> */
		public readonly array $limits,
		/** @var ?class-string<InvoiceModifier> */
		public readonly ?string $invoiceModifier,
		/** @var ?class-string<InputModifier> */
		public readonly ?string $inputModifier,
	) {
	}

	public static function from(
		array $entries,
	): self {
		$allLocales = $entries['supportedLocales'] ?? ['en', 'cs'];
		$fees = Fees::fromRoot($entries['fees'] ?? []);
		$eventDate = $entries['eventDate'];
		$minMembers = $entries['minMembers'] ?? 0;
		$personFieldsRaw = Helpers::ensureFields('person', $entries['fields']['person'] ?? []);
		$personFieldsKeys = array_keys($personFieldsRaw);
		$teamFieldsRaw = Helpers::ensureFields('team', $entries['fields']['team'] ?? []);
		$teamFieldsKeys = array_keys($teamFieldsRaw);

		return new self(
			initialMembers: $entries['initialMembers'] ?? $minMembers,
			minMembers: $minMembers,
			maxMembers: $entries['maxMembers'] ?? null,
			allowPlaceholders: $entries['allowPlaceholders'] ?? false,
			eventDate: $eventDate,
			allowLateRegistrationsByEmail: $entries['allowLateRegistrationsByEmail'] ?? false,
			recommendedCardCapacity: $entries['recommendedCardCapacity'] ?? 0,
			fees: $fees,
			categories: CategoryData::from(
				$entries['categories'] ?? [],
				$fees,
				$eventDate,
				$allLocales,
			),
			personFields: array_combine(
				$personFieldsKeys,
				array_map(
					fn(string $name, array $field): Field => Helpers::makeField($name, $field, $allLocales, $fees),
					$personFieldsKeys,
					$personFieldsRaw,
				),
			),
			teamFields: array_combine(
				$teamFieldsKeys,
				array_map(
					fn(string $name, array $field): Field => Helpers::makeField($name, $field, $allLocales, $fees),
					$teamFieldsKeys,
					$teamFieldsRaw,
				),
			),
			opening: $entries['opening'] ?? null,
			closing: $entries['closing'] ?? null,
			limits: Helpers::ensureLimits($entries['limits'] ?? []),
			invoiceModifier: Helpers::ensureSubclassOf($entries['invoiceModifier'] ?? null, InvoiceModifier::class),
			inputModifier: Helpers::ensureSubclassOf($entries['inputModifier'] ?? null, InputModifier::class),
		);
	}
}
