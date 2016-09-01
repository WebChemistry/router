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
	 */
	public function getModule($module, $priority = NULL) {
		if ($priority === NULL) {
			if ($this->isMain) {
				$priority = 10;
			} else {
				$priority = 1;
			}
		}
		if (!isset($this->modules[$module][$priority])) {
			$this->createModule($module, $priority);
		}

		return $this->modules[$module][$priority];
	}

	/**
	 * @param string $module
	 * @param int $priority
	 * @throws RouterException
	 */
	protected function createModule($module, $priority) {
		if ($priority == 10 && !$this->isMain) {
			throw new RouterException('Only main router can set priority 10.');
		}
		if ($priority < 1 || $priority > 10) {
			throw new OutOfRangeException('Priority out of range.');
		}
		if (!$this->isMain && !isset($this->modules[$module])) {
			$this->insertBefore($module, []);
		}
		$this->modules[$module][$priority] = new RouteList($module);
	}

	protected function insertBefore($key, $value) {
		$new[$key] = $value;
		foreach ($this->modules as $name => $array) {
			$new[$name] = $array;
		}
		$this->modules = $new;
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
			ksort($values);
			/** @var RouteList $list */
			foreach ($values as $list) {
				foreach ($list->getIterator() as $route) {
					$routeList[] = $route;
				}
			}
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
