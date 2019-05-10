<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

/**
 * Factory for routes.
 */
final class RouterFactory {
	use Nette\StaticClass;

	public static function createRouter(): RouteList {
		$router = new RouteList();
		$router[] = new Route('[<locale>/]<presenter>/<action>[/<id>]', 'Homepage:default');

		return $router;
	}
}
