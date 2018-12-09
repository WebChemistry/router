<?php declare(strict_types = 1);

namespace WebChemistry\Routing;

interface IRouter {

	/**
	 * @param RouteManager $manager
	 * @return void
	 */
	public function createRouter(RouteManager $manager): void;

}
