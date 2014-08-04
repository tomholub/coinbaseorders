<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Diagnostics\Debugger;

/**
 * Router factory.
 */
class RouterFactory {

	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter() {
		$router = new RouteList();
		$flags = Debugger::$productionMode ? Route::SECURED : null;
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default', $flags);
		return $router;
	}

}