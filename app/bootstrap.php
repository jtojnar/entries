<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator();

$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');
if (file_exists(__DIR__ . '/config/private.neon')) {
	$configurator->addConfig(__DIR__ . '/config/private.neon');
}

$container = $configurator->createContainer();

return $container;
