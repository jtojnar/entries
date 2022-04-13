<?php

declare(strict_types=1);

namespace App\Model;

use App;
use Closure;
use Nette;
use Nette\Application\Application;
use Nette\Forms\Controls\SelectBox;
use Nette\Localization\Translator;
use const nspl\a\all;
use const nspl\a\any;
use function nspl\a\map;
use const nspl\f\id;
use const nspl\op\ge;
use const nspl\op\gt;
use const nspl\op\idnt;
use const nspl\op\int;
use const nspl\op\le;
use const nspl\op\lt;

final class CategoryData {
	use Nette\SmartObject;

	/** @var Application */
	private $app;

	/** @var Translator */
	private $translator;

	/** @var array */
	private $parameters;

	/** @var non-empty-array */
	private $categoryTree;

	/** @var non-empty-array */
	private $categoryData;

	public const CONSTRAINT_REGEX = '(^\s*(?P<quant>all|some)\((?P<key>age|gender)(?P<op>[<>]?=?)(?P<val>.+)\)$\s*)';
	public const AGGREGATE_CONSTRAINT_REGEX = '(^\s*(?P<aggr>(sum|min|max))\((?P<key>age)\)(?P<op>[<>]?=?)(?P<val>[0-9]+)$\s*)';

	public const OP_LOOKUP = [
		'<' => lt,
		'<=' => le,
		'=' => idnt,
		'>=' => ge,
		'>' => gt,
	];

	public const AGGR_LOOKUP = [
		'sum' => 'array_sum',
		'min' => 'min',
		'max' => 'max',
	];

	public const KEY_SUPPORTED_OPS = [
		'age' => ['<', '<=', '=', '>=', '>'],
		'gender' => ['='],
	];

	public const KEY_PROJECTIONS_LOOKUP = [
		'age' => 'ageProjection',
		'gender' => 'genderProjection',
	];

	public const VALUE_PARSERS = [
		'age' => int,
		'gender' => id,
	];

	public const KEY_MESSAGES = [
		'gender' => 'messages.team.error.gender_mismatch',
		'age' => 'messages.team.error.age_mismatch',
	];

	public const QUANT_LOOKUP = [
		'all' => all,
		'some' => any,
	];

	public function __construct(Application $app, Nette\DI\Container $context, Translator $translator) {
		$this->app = $app;
		$this->parameters = $context->parameters['entries'];
		$this->translator = $translator;
	}

	/**
	 * Check whether categories in config.neon are nested.
	 *
	 * @return non-empty-array
	 */
	public function getCategoryTree(): array {
		if (!isset($this->categoryTree)) {
			/** @var \App\Presenters\BasePresenter */
			$presenter = $this->app->getPresenter();
			$locale = $presenter->locale;
			$items = [];

			if (\count($this->parameters['categories']) === 0) {
				throw new \Exception('No categories defined.');
			}

			if (self::isNested($this->parameters['categories'])) {
				$categoryData = [];
				$groups = $this->parameters['categories'];

				$groupsKeys = array_map(function(string $groupKey) use ($groups, $locale): string {
					$group = $groups[$groupKey];

					if (isset($group['label'])) {
						if (\is_array($group['label']) && isset($group['label'][$locale])) {
							return $group['label'][$locale];
						} elseif (\is_string($group['label'])) {
							return $group['label'];
						}
					}

					throw new \Exception("Category group #${groupKey} lacks a label");
				}, array_keys($groups));

				$groupsCategories = array_map(function(string $groupKey) use ($groups): array {
					$group = $groups[$groupKey];

					if (!isset($group['categories']) || !\is_array($group['categories']) || \count($group['categories']) === 0) {
						throw new \Exception("Category group #${groupKey} lacks categories");
					}

					$categories = $group['categories'];

					$categoriesKeys = array_keys($categories);

					$categoriesData = array_map(function(string $categoryKey) use ($categories, $group): array {
						$category = $categories[$categoryKey];

						if (isset($category['fees']) && isset($category['fees']['person'])) {
							$fee = $category['fees']['person'];
						} elseif (isset($group['fees']) && isset($group['fees']['person'])) {
							$fee = $group['fees']['person'];
						} elseif (isset($this->parameters['fees']) && isset($this->parameters['fees']['person'])) {
							$fee = $this->parameters['fees']['person'];
						} else {
							throw new \Exception("No fee set for category “${categoryKey}”");
						}

						$categoryValue = [
							'label' => $categoryKey,
							'fee' => $fee,
							'constraints' => $this->parseConstraints($category),
							'minMembers' => $category['minMembers'] ?? null,
							'maxMembers' => $category['maxMembers'] ?? null,
						];

						return $categoryValue;
					}, $categoriesKeys);

					return array_combine($categoriesKeys, $categoriesData);
				}, array_keys($groups));

				$categoryTree = array_combine($groupsKeys, $groupsCategories);

				$categoryData = [];
				foreach ($categoryTree as $categories) {
					foreach ($categories as $categoryKey => $categoryValue) {
						if (isset($categoryData[$categoryKey])) {
							throw new \Exception("Category “${categoryKey}” is already defined");
						}

						$categoryData[$categoryKey] = $categoryValue;
					}
				}

				if (\count($categoryData) === 0) {
					throw new \PHPStan\ShouldNotHappenException();
				}

				$this->categoryData = $categoryData;
				$this->categoryTree = $categoryTree;
			} else {
				$categories = $this->parameters['categories'];

				$categoriesKeys = array_keys($categories);

				$categoriesData = array_map(function(string $categoryKey) use ($categories): array {
					$category = $categories[$categoryKey] ?: [];

					if (isset($category['fees']) && isset($category['fees']['person'])) {
						$fee = $category['fees']['person'];
					} elseif (isset($this->parameters['fees']) && isset($this->parameters['fees']['person'])) {
						$fee = $this->parameters['fees']['person'];
					} else {
						throw new \Exception("No fee set for category “${categoryKey}”");
					}

					return [
						'label' => $categoryKey,
						'fee' => $fee,
						'constraints' => $this->parseConstraints($category),
						'minMembers' => $category['minMembers'] ?? null,
						'maxMembers' => $category['maxMembers'] ?? null,
					];
				}, array_keys($categories));

				$categoryTree = array_combine($categoriesKeys, $categoriesData);

				if (\count($categoryTree) === 0) {
					throw new \PHPStan\ShouldNotHappenException();
				}

				$this->categoryTree = $categoryTree;
				$this->categoryData = $categoryTree;
			}
		}

		return $this->categoryTree;
	}

	public function getCategoryData(): array {
		if (!isset($this->categoryData)) {
			$this->getCategoryTree();
		}

		return $this->categoryData;
	}

	/**
	 * Check whether categories in config.neon are nested.
	 *
	 * @param non-empty-array $categories
	 */
	private static function isNested(array $categories): bool {
		foreach ($categories as $category) {
			return isset($category['categories']);
		}
	}

	/**
	 * Check whether categories are nested.
	 */
	public function areNested(): bool {
		foreach ($this->getCategoryTree() as $category) {
			return !isset($category['label']);
		}
	}

	private function parseConstraints(array $category): array {
		if (!isset($category['constraints'])) {
			return [];
		}

		return array_map(function(string $constraint): array {
			if (preg_match(self::CONSTRAINT_REGEX, $constraint, $match)) {
				$quant = Closure::fromCallable(self::QUANT_LOOKUP[$match['quant']]);
				$op = Closure::fromCallable(self::OP_LOOKUP[$match['op']]);
				/** @var callable */
				$keyProjection = [$this, self::KEY_PROJECTIONS_LOOKUP[$match['key']]];
				$keyProjection = Closure::fromCallable($keyProjection);
				$message = self::KEY_MESSAGES[$match['key']];

				if (!\in_array($match['op'], self::KEY_SUPPORTED_OPS[$match['key']], true)) {
					throw new \Exception("Constraint “${constraint}” is invalid: using ‘${match['op']}’ with ‘${match['key']}’ is not supported.");
				}

				$comparedValue = Closure::fromCallable(self::VALUE_PARSERS[$match['key']])($match['val']);

				return [
					function(SelectBox $entry) use ($quant, $keyProjection, $op, $comparedValue): bool {
						/** @var App\Components\TeamForm */
						$form = $entry->getForm();
						$members = $form->getUnsafeValues(null)['persons'];

						return $quant($members, function(\ArrayAccess $person) use ($op, $keyProjection, $comparedValue): bool {
							return $op($keyProjection($person), $comparedValue);
						});
					},
					$this->translator->translate($message),
				];
			} elseif (preg_match(self::AGGREGATE_CONSTRAINT_REGEX, $constraint, $match)) {
				$aggr = Closure::fromCallable(self::AGGR_LOOKUP[$match['aggr']]);
				/** @var callable */
				$keyProjection = [$this, self::KEY_PROJECTIONS_LOOKUP[$match['key']]];
				$keyProjection = Closure::fromCallable($keyProjection);
				$op = Closure::fromCallable(self::OP_LOOKUP[$match['op']]);
				$message = self::KEY_MESSAGES[$match['key']];

				$comparedValue = Closure::fromCallable(self::VALUE_PARSERS[$match['key']])($match['val']);

				return [
					function(SelectBox $entry) use ($aggr, $keyProjection, $op, $comparedValue): bool {
						/** @var App\Components\TeamForm */
						$form = $entry->getForm();
						$members = iterator_to_array($form->getUnsafeValues(null)['persons']);

						return $op($aggr(map($keyProjection, $members)), $comparedValue);
					},
					$this->translator->translate($message),
				];
			}

			throw new \Exception("Constraint “${constraint}” is invalid");
		}, $category['constraints']);
	}

	private function ageProjection(\ArrayAccess $person): ?int {
		if (!isset($person['birth'])) {
			return null;
		}

		$eventDate = $this->parameters['eventDate'];
		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age;
	}

	private function genderProjection(\ArrayAccess $person): ?string {
		return $person['gender'];
	}
}
