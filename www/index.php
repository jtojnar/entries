<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Uncomment this line if you must temporarily take down your site for maintenance.
// require '.maintenance.php';

App\Booting::boot()
	->createContainer()
	->getByType(Nette\Application\Application::class)
	->run();
