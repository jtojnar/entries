<?php

declare(strict_types=1);

namespace App;

use App\Model\CategoryData;
use Nette;

class EntriesExtension extends Nette\DI\CompilerExtension {
	/** @var array $extraMessages */
	private $extraMessages;

	/** @var string $defaultLocale */
	private $defaultLocale;

	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();
		$config = $builder->parameters['entries'];
		$this->defaultLocale = $builder->parameters['defaultLocale'];

		list($categories, $nested) = $this->getCategories($config);
		$builder->addDefinition($this->prefix('categoryEntry'))
			->setClass('Nette\\Forms\\Controls\\SelectBox')
			->addSetup('setItems', [self::getLabels($categories, $nested)])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('categorySelector'))
			->setClass('Nette\\Forms\\Controls\\SelectBox')
			->addSetup('setItems', [self::getLabels($categories, $nested, true)])
			->setAutowired(false);
	}

	public function beforeCompile() {
		$builder = $this->getContainerBuilder();
		$service = $builder->getDefinition('translation.default');

		foreach ($this->extraMessages as $locale => $messages) {
			$service->addSetup('addResource', ['array', $messages, $locale, 'entries']);
		}
	}

	private function addMessage(string $key, string $value, string $locale = null) {
		if ($locale === null) {
			$locale = $this->defaultLocale;
		}

		if (!isset($this->extraMessages[$locale])) {
			$this->extraMessages[$locale] = [];
		}

		$this->extraMessages[$locale][$key] = $value;
	}

	private function getCategories(array $config): array {
		if (count($config['categories']) === 0) {
			throw new \Exception('No categories defined.');
		}

		$nested = self::isNested($config['categories']);

		if ($nested) {
			$groups = $config['categories'];

			$groupsKeys = array_map(function(string $groupKey) use ($groups): string {
				$group = $groups[$groupKey];

				if (isset($group['label'])) {
					$label = 'team.categoryGroup.' . $groupKey;

					if (is_array($group['label'])) {
						foreach ($group['label'] as $locale => $value) {
							$this->addMessage($label, $value, $locale);
						}
					} elseif (is_string($group['label'])) {
						$this->addMessage($label, $group['label']);
					}

					return 'entries.' . $label;
				} else {
					throw new \Exception("Category group #${groupKey} lacks a label");
				}
			}, array_keys($groups));

			$groupsCategories = array_map(function(string $groupKey) use ($groups, $config): array {
				$group = $groups[$groupKey];

				if (!isset($group['categories']) || !is_array($group['categories'])) {
					throw new \Exception("Category group #${groupKey} lacks categories");
				}

				$categories = $group['categories'];

				$categoriesKeys = array_keys($categories);

				$categoriesData = array_map(function(string $categoryKey) use ($categories, $group, $config): array {
					$category = $categories[$categoryKey];

					if (isset($category['fees']) && isset($category['fees']['person'])) {
						$fee = $category['fees']['person'];
					} elseif (isset($group['fees']) && isset($group['fees']['person'])) {
						$fee = $group['fees']['person'];
					} elseif (isset($config['fees']) && isset($config['fees']['person'])) {
						$fee = $config['fees']['person'];
					} else {
						throw new \Exception("No fee set for category “${category}”");
					}

					$label = 'team.category.' . $categoryKey;
					$this->addMessage($label, $categoryKey);
					$categoryValue = [
						'label' => 'entries.' . $label,
						'fee' => $fee,
						'constraints' => CategoryData::parseConstraints($category, $config),
					];

					return $categoryValue;
				}, array_keys($categories));

				return array_combine($categoriesKeys, $categoriesData);
			}, array_keys($groups));

			$categories = array_combine($groupsKeys, $groupsCategories);
		} else {
			$categories = $config['categories'];

			$categoriesKeys = array_keys($categories);

			$categoriesData = array_map(function(string $categoryKey) use ($categories, $config): array {
				$category = $categories[$categoryKey];

				if (isset($category['fees']) && isset($category['fees']['person'])) {
					$fee = $category['fees']['person'];
				} elseif (isset($config['fees']) && isset($config['fees']['person'])) {
					$fee = $config['fees']['person'];
				} else {
					throw new \Exception("No fee set for category “${category}”");
				}

				$label = 'team.category.' . $categoryKey;
				$this->addMessage($label, $categoryKey);
				$categoryValue = [
					'label' => 'entries.' . $label,
					'fee' => $fee,
					'constraints' => CategoryData::parseConstraints($category, $config),
				];

				return $categoryValue;
			}, array_keys($categories));

			$categories = array_combine($categoriesKeys, $categoriesData);
		}

		return [$categories, $nested];
	}

	private static function getLabels(array $categories, bool $nested, bool $showAll = false): array {
		if ($nested) {
			$items = array_map(function(array $group) use ($showAll): array {
				$categoryArray = array_map(['self', 'labelKey'], $group);

				if ($showAll) {
					$allKey = implode('|', array_keys($group));
					$categoryArray = [$allKey => 'messages.team.list.filter.category.all'] + $categoryArray;
				}

				return $categoryArray;
			}, $categories);
		} else {
			$items = array_map(['self', 'labelKey'], $categories);
		}

		return $items;
	}

	private static function labelKey(array $category): string {
		return $category['label'];
	}

	private static function isNested(array $categories): bool {
		foreach ($categories as $category) {
			return isset($category['categories']);
		}

		return false;
	}
}
