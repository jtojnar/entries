<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;

final class Bootstrap {
	public static function boot(): Configurator {
		$configurator = new Configurator();
		// $configurator->setDebugMode('23.75.345.200'); // enable for your remote IP
		$configurator->enableTracy(__DIR__ . '/../log');
		$configurator->setTimeZone('UTC');
		$configurator->setTempDirectory(__DIR__ . '/../temp');
		$configurator
			->addConfig(__DIR__ . '/config/common.neon');
		$configurator
			->addConfig(__DIR__ . '/config/local.neon');
		if (file_exists(__DIR__ . '/config/private.neon')) {
			$configurator->addConfig(__DIR__ . '/config/private.neon');
		}
		$configurator
			->addConfig(__DIR__ . '/lang/locales.neon');

		return $configurator;
	}

	public static function bootForTests(): Configurator {
		$configurator = self::boot();
		$tempDir = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'entries-tests.' . mt_rand();
		mkdir($tempDir);
		$configurator->setTempDirectory($tempDir);
		$configurator->addConfig(__DIR__ . '/../tests/config.neon');
		\Tester\Environment::setup();

		return $configurator;
	}
}
