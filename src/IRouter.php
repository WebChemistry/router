<?php

namespace WebChemistry\Routing;

interface IRouter {

	/**
	 * @param RouteManager $manager
	 * @return void
	 */
	public function createRouter(RouteManager $manager);

}
