<?php

declare(strict_types=1);

namespace App\Tests;

use App\Helpers\Iter;
use Nette;
use Tester\Assert;

require __DIR__ . '/../vendor/autoload.php';

$configurator = \App\Bootstrap::bootForTests();

$container = $configurator->createContainer();
$translator = $container->getByType(Nette\Localization\Translator::class);

foreach (['en', 'cs'] as $locale) {
	$catalogue = $translator->getCatalogue($locale);
	$messages = $catalogue->all()['messages'];

	foreach ($messages as $key => $message) {
		$messageHasVariables = preg_match_all('/\{(?P<var>[^},]+)(?:,(?P<type>[^},]+))?/', $message, $varMatches, \PREG_SET_ORDER | \PREG_UNMATCHED_AS_NULL);

		if (!$messageHasVariables) {
			Assert::noError(function() use ($translator, $key): void {
				$translator->translate('messages.' . $key);
			});

			continue;
		}

		// Collect variables.
		$variables = [];
		foreach ($varMatches as $match) {
			$var = trim($match['var']);
			$type = trim($match['type'] ?? '');
			Assert::contains($type, ['plural', '']);
			if (!isset($variables[$var]) || $type === 'plural') {
				$variables[$var] = $type;
			}
		}

		// Create fake data for common variants.
		foreach ($variables as $var => $type) {
			if ($type === 'plural') {
				$variables[$var] = [0, 1, 3, 5];
			} elseif ($type === '') {
				$variables[$var] = [342];
			}
		}

		// Try to translate the message.
		$assignments = Iter::cartesianProduct($variables);
		foreach ($assignments as $assignment) {
			Assert::noError(function() use ($translator, $key, $assignment): void {
				$translator->translate('messages.' . $key, $assignment);
			});
		}
	}
}
