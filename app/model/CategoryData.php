<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Utils\Callback;

class CategoryData {
	use Nette\SmartObject;

	/** Nette\Application\Application */
	private $app;

	/** @var array */
	private $parameters;

	/** @var string */
	private $locale;

	/** @var array */
	private $categoryTree;

	/** @var array */
	private $categoryData;

	public const CONSTRAINT_REGEX = '(^\s*(?P<quant>all|some)\((?P<key>age|gender)(?P<op>[<>]?=?)(?P<val>.+)\)$\s*)';

	public const OP_LOOKUP = [
		'<' => 'lt',
		'<=' => 'lte',
		'=' => 'eq',
		'>=' => 'gte',
		'>' => 'gt',
	];

	public const KEY_SUPPORTED_OPS = [
		'age' => ['<', '<=', '=', '>=', '>'],
		'gender' => ['='],
	];

	public const KEY_PROJECTIONS_LOOKUP = [
		'age' => 'ageProjection',
		'gender' => 'genderProjection',
	];

	public const VALUE_PARSERS_LOOKUP = [
		'age' => 'ageParser',
		'gender' => 'genderParser',
	];

	public const KEY_MESSAGES = [
		'gender' => 'messages.team.error.gender_mismatch',
		'age' => 'messages.team.error.age_mismatch',
	];

	public const QUANT_LOOKUP = [
		'all' => 'quantAll',
		'some' => 'quantSome',
	];

	public function __construct(Nette\Application\Application $app) {
		$this->app = $app;
	}

	public function getCategoryTree() {
		if (!isset($this->categoryTree)) {
			$presenter = $this->app->getPresenter();
			$parameters = $this->parameters = $presenter->context->parameters['entries'];
			$locale = $this->locale = $presenter->locale;
			$items = [];

			if (\count($parameters['categories']) === 0) {
				throw new \Exception('No categories defined.');
			}

			if (self::isNested($parameters['categories'])) {
				$this->categoryData = [];
				$groups = $parameters['categories'];

				$groupsKeys = array_map(function($groupKey) use ($groups, $locale) {
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

				$groupsCategories = array_map(function($groupKey) use ($groups, $parameters) {
					$group = $groups[$groupKey];

					if (!isset($group['categories']) || !\is_array($group['categories'])) {
						throw new \Exception("Category group #${groupKey} lacks categories");
					}

					$categories = $group['categories'];

					$categoriesKeys = array_keys($categories);

					$categoriesData = array_map(function($categoryKey) use ($categories, $group, $parameters) {
						$category = $categories[$categoryKey];

						if (isset($category['fees']) && isset($category['fees']['person'])) {
							$fee = $category['fees']['person'];
						} elseif (isset($group['fees']) && isset($group['fees']['person'])) {
							$fee = $group['fees']['person'];
						} elseif (isset($parameters['fees']) && isset($parameters['fees']['person'])) {
							$fee = $parameters['fees']['person'];
						} else {
							throw new \Exception("No fee set for category “${category}”");
						}

						$categoryValue = [
							'label' => $categoryKey,
							'fee' => $fee,
							'constraints' => $this->parseConstraints($category),
						];

						if (isset($this->categoryData[$categoryKey])) {
							throw new \Exception("Category “${categoryKey}” is already defined");
						}

						$this->categoryData[$categoryKey] = $categoryValue;

						return $categoryValue;
					}, array_keys($categories));

					return array_combine($categoriesKeys, $categoriesData);
				}, array_keys($groups));

				$this->categoryTree = array_combine($groupsKeys, $groupsCategories);
			} else {
				$categories = $parameters['categories'];

				$categoriesKeys = array_keys($categories);

				$categoriesData = array_map(function($categoryKey) use ($categories, $parameters) {
					$category = $categories[$categoryKey];

					if (isset($category['fees']) && isset($category['fees']['person'])) {
						$fee = $category['fees']['person'];
					} elseif (isset($parameters['fees']) && isset($parameters['fees']['person'])) {
						$fee = $parameters['fees']['person'];
					} else {
						throw new \Exception("No fee set for category “${category}”");
					}

					return [
						'label' => $categoryKey,
						'fee' => $fee,
						'constraints' => $this->parseConstraints($category),
					];
				}, array_keys($categories));

				$this->categoryTree = array_combine($categoriesKeys, $categoriesData);
				$this->categoryData = $this->categoryTree;
			}
		}

		return $this->categoryTree;
	}

	public function getCategoryData() {
		if (!isset($this->categoryData)) {
			$this->getCategoryTree();
		}

		return $this->categoryData;
	}

	private static function isNested($categories) {
		foreach ($categories as $category) {
			return isset($category['categories']);
		}
	}

	public function areNested() {
		foreach ($this->getCategoryTree() as $category) {
			return !isset($category['label']);
		}
	}

	private function parseConstraints($category) {
		if (!isset($category['constraints'])) {
			return [];
		}

		return array_map(function($constraint) {
			if (preg_match(self::CONSTRAINT_REGEX, $constraint, $match)) {
				$quant = Callback::closure($this, self::QUANT_LOOKUP[$match['quant']]);
				$op = Callback::closure($this, self::OP_LOOKUP[$match['op']]);
				$keyProjection = Callback::closure($this, self::KEY_PROJECTIONS_LOOKUP[$match['key']]);
				$message = self::KEY_MESSAGES[$match['key']];

				if (!\in_array($match['op'], self::KEY_SUPPORTED_OPS[$match['key']], true)) {
					throw new \Exception("Constraint “${constraint}” is invalid: using ‘${match['op']}’ with ‘${match['key']}’ is not supported.");
				}

				$val = Callback::closure($this, self::VALUE_PARSERS_LOOKUP[$match['key']])($match['val']);

				$translator = $this->app->getPresenter()->translator;

				return [
					function($entry) use ($quant, $keyProjection, $op, $val) {
						return $quant($entry->getForm(), $keyProjection, $op, $val);
					},
					$translator->translate($message),
				];
			}

			throw new \Exception("Constraint “${constraint}” is invalid");
		}, $category['constraints']);
	}

	private function lt($a, $b) {
		return $a < $b;
	}

	private function lte($a, $b) {
		return $a <= $b;
	}

	private function eq($a, $b) {
		return $a === $b;
	}

	private function gte($a, $b) {
		return $a >= $b;
	}

	private function gt($a, $b) {
		return $a > $b;
	}

	private function ageParser(string $age): int {
		return (int) $age;
	}

	private function genderParser(string $gender): string {
		return $gender;
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

	private function quantAll($team, \Closure $keyProjection, \Closure $op, $value) {
		foreach ($team['persons']->values as $person) {
			if (!$op($keyProjection($person), $value)) {
				return false;
			}
		}

		return true;
	}

	private function quantSome($team, \Closure $keyProjection, \Closure $op, $value) {
		foreach ($team['persons']->values as $person) {
			if ($op($keyProjection($person), $value)) {
				return true;
			}
		}

		return false;
	}
}
