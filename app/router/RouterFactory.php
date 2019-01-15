<?php

declare(strict_types=1);

namespace App;

use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

class RouterFactory {
	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter() {
		$router = new RouteList();
		$router[] = new Route('[<locale>/]<presenter>/<action>[/<id>]', 'Homepage:default');

		return $router;
	}
}
