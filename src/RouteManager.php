<?php

namespace WebChemistry\Routing;

use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\InvalidArgumentException;
use Nette\OutOfRangeException;

class RouteManager {

	/** @var array */
	private $modules = [];

	/** @var array */
	private $routers = [];

	/** @var bool */
	private $isMain = TRUE;

	/** @var bool */
	private $finished = FALSE;

	/** @var array */
	private $forbiddenRouters = [];

	public function __construct($mainRouter, $routers) {
		$this->routers = $routers;
		array_unshift($this->routers, $mainRouter);
	}

	/**
	 * @param string $style
	 * @param string $parent
	 */
	public function addStyle($style, $parent = '#') {
		if (isset(Route::$styles[$style])) {
			return;
		}
		if ($parent !== NULL) {
			if (!isset(Route::$styles[$parent])) {
				throw new InvalidArgumentException("Parent style '$parent' doesn't exist.");
			}
			Route::$styles[$style] = Route::$styles[$parent];

		} else {
			Route::$styles[$style] = array();
		}
	}

	/**
	 * @param string $style
	 * @param string $key
	 * @param callable $value
	 */
	public function setStyleProperty($style, $key, $value) {
		if (!isset(Route::$styles[$style])) {
			throw new InvalidArgumentException("Style '$style' doesn't exist.");
		}
		Route::$styles[$style][$key] = $value;
	}

	/**
	 * @param string $module
	 * @param int $priority
	 * @return array
	 */
	public function getModule($module, $priority = NULL) {
		if ($priority === NULL) {
			$priority = $this->isMain ? 10 : 0;
		}
		$this->checkPriority($priority);
		if (!isset($this->modules[$module])) {
			$this->createModule($module);
		}
		if ($this->modules[$module][$priority] === NULL) {
			$this->modules[$module][$priority] = new RouteList($module);
		}

		return $this->modules[$module][$priority];
	}

	protected function checkPriority($priority) {
		if ($priority === 10 && !$this->isMain) {
			throw new RouterException('Only main router can set priority 10.');
		}
		if ($priority < 0 || $priority > 10) {
			throw new OutOfRangeException('Priority out of range.');
		}
	}

	/**
	 * @param string $module
	 * @throws RouterException
	 */
	protected function createModule($module) {
		$this->modules[$module] = array_fill(0, 11, NULL);
	}

	/**
	 * @param string $router
	 * @return string
	 */
	private function getClass($router) {
		return is_object($router) ? get_class($router) : $router;
	}

	/**
	 * @return RouteList
	 * @throws RouterException
	 */
	public function createRouter() {
		foreach ($this->routers as $router) {
			if (array_search($this->getClass($router), $this->forbiddenRouters) !== FALSE) {
				continue;
			}
			if (!is_object($router)) {
				$router = new $router;
			}
			if (!$router instanceof IRouter) {
				throw new RouterException('Class ' . get_class($router) . ' must implements ' . IRouter::class);
			}
			$router->createRouter($this);
			$this->isMain = FALSE;
			if ($this->finished) {
				break;
			}
		}

		$return = new RouteList();
		foreach ($this->modules as $module => $values)	 {
			$routeList = new RouteList($module);
			/** @var RouteList $list */
			foreach ($values as $list) {
				if ($list) {
					foreach ($list->getIterator() as $route) {
						$routeList[] = $route;
					}
				}
			}
			$routeList->warmupCache();
			$return[] = $routeList;
		}

		return $return;
	}

	public function finish() {
		if (!$this->isMain) {
			throw new RouterException('Only main router can call this method.');
		}
		$this->finished = TRUE;
	}

	/**
	 * @param array $forbiddenRouters
	 * @return RouteManager
	 * @throws RouterException
	 */
	public function setForbiddenRouters(array $forbiddenRouters) {
		if (!$this->isMain) {
			throw new RouterException('Only main router can set forbidden routers.');
		}
		$this->forbiddenRouters = $forbiddenRouters;

		return $this;
	}

}
