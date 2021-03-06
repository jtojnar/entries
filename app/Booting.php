<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;

class Booting {
	public static function boot(): Configurator {
		$configurator = new Configurator();
		//$configurator->setDebugMode('23.75.345.200'); // enable for your remote IP
		$configurator->enableTracy(__DIR__ . '/../log');
		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory(__DIR__ . '/../temp');
		$configurator
			->addConfig(__DIR__ . '/config/config.neon');
		$configurator
			->addConfig(__DIR__ . '/config/config.local.neon');
		if (file_exists(__DIR__ . '/config/private.neon')) {
			$configurator->addConfig(__DIR__ . '/config/private.neon');
		}
		$configurator
			->addConfig(__DIR__ . '/lang/locales.neon');

		return $configurator;
	}

	public static function bootForTests(): Configurator {
		$configurator = self::boot();
		\Tester\Environment::setup();

		return $configurator;
	}
}
