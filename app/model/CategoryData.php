<?php

namespace App\Model;

use Nette;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
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

	const CONSTRAINT_REGEX = '(^\s*(?P<quant>all|some)\((?P<op>age[<>]?=?|gender=)(?P<val>.+)\)$\s*)';

	const OP_LOOKUP = [
		'age<' => 'ageLt',
		'age<=' => 'ageLe',
		'age=' => 'ageEq',
		'age>=' => 'ageGe',
		'age>' => 'ageGt',
		'gender=' => 'genderEq',
	];

	const QUANT_LOOKUP = [
		'all' => 'quantAll',
		'some' => 'quantSome',
	];

	public function __construct(Nette\Application\Application $app) {
		$this->app = $app;
	}

	private function getCategoryTree() {
		if (!isset($this->categoryTree)) {
			$presenter = $this->app->getPresenter();
			$parameters = $this->parameters = $presenter->context->parameters['entries'];
			$locale = $this->locale = $presenter->locale;
			$items = [];

			if (count($parameters['categories']) === 0) {
				throw new \Exception('No categories defined.');
			}

			if (self::isNested($parameters['categories'])) {
				$this->categoryData = [];
				$groups = $parameters['categories'];

				$groupsKeys = array_map(function($groupKey) use ($groups, $locale) {
					$group = $groups[$groupKey];

					if (isset($group['label'])) {
						if (is_array($group['label']) && isset($group['label'][$locale])) {
							return $group['label'][$locale];
						} elseif (is_string($group['label'])) {
							return $group['label'];
						}
					}

					throw new \Exception("Category group #${groupKey} lacks a label");
				}, array_keys($groups));

				$groupsCategories = array_map(function($groupKey) use ($groups, $parameters) {
					$group = $groups[$groupKey];

					if (!isset($group['categories']) || !is_array($group['categories'])) {
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
							'constraints' => self::parseConstraints($category, $parameters),
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
						'constraints' => self::parseConstraints($category, $parameters),
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

	public static function parseConstraints(array $category, array $config): array {
		if (!isset($category['constraints'])) {
			return [];
		}

		return array_map(function(string $constraint) use ($config) {
			if (preg_match(self::CONSTRAINT_REGEX, $constraint, $match)) {
				$quant = Callback::closure(self::class, self::QUANT_LOOKUP[$match['quant']]);
				$op = Callback::closure(self::class, self::OP_LOOKUP[$match['op']]);
				$val = $match['val'];

				if ($match['op'] === 'gender=') {
					$message = 'messages.team.error.gender_mismatch';
				} else {
					$message = 'messages.team.error.age_mismatch';
				}

				return [
					function(BaseControl $entry) use ($quant, $op, $val, $config) {
						return $quant($entry->getForm(), $op, $val, $config);
					},
					$message
				];
			}

			throw new \Exception("Constraint “${constraint}” is invalid");
		}, $category['constraints']);
	}

	private static function ageLt(ArrayHash $person, $value, array $config): bool {
		if (!isset($person['birth'])) {
			return true;
		}

		$eventDate = $config['eventDate'];
		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age < (int) $value;
	}

	private static function ageLe(ArrayHash $person, $value, array $config): bool {
		if (!isset($person['birth'])) {
			return true;
		}

		$eventDate = $config['eventDate'];
		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age <= (int) $value;
	}

	private static function ageEq(ArrayHash $person, $value, array $config): bool {
		if (!isset($person['birth'])) {
			return true;
		}

		$eventDate = $config['eventDate'];
		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age === (int) $value;
	}

	private static function ageGe(ArrayHash $person, $value, array $config): bool {
		if (!isset($person['birth'])) {
			return true;
		}

		$eventDate = $config['eventDate'];
		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age >= (int) $value;
	}

	private static function ageGt(ArrayHash $person, $value, array $config): bool {
		if (!isset($person['birth'])) {
			return true;
		}

		$eventDate = $config['eventDate'];
		$age = $diff = $person['birth']->diff($eventDate, true)->y;

		return $age > (int) $value;
	}

	private static function genderEq(ArrayHash $person, $value, array $config): bool {
		return $person['gender'] === $value;
	}

	private static function quantAll(Form $team, \Closure $fn, $value, array $config): bool {
		foreach ($team['persons']->values as $person) {
			if (!$fn($person, $value, $config)) {
				return false;
			}
		}

		return true;
	}

	private static function quantSome(Form $team, \Closure $fn, $value, array $config): bool {
		foreach ($team['persons']->values as $person) {
			if ($fn($person, $value, $config)) {
				return true;
			}
		}

		return false;
	}
}
