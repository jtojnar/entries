<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
	if (is_file(__DIR__ . $path)) {
		return false;
	}
}

require __DIR__ . '/../vendor/autoload.php';

$configurator = App\Bootstrap::boot();
$container = $configurator->createContainer();
$application = $container->getByType(Nette\Application\Application::class);
$application->run();
