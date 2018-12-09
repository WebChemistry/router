<?php declare(strict_types = 1);

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
	private $createStage = false;

	public function __construct(array $routers, array $modules) {
		$this->routers = $routers;
		foreach ($modules as $module) {
			$this->createModule($module);
		}
	}

	/**
	 * @param IRouter $router
	 * @return static
	 */
	public function addRouter(IRouter $router) {
		$this->routers[] = $router;

		return $this;
	}

	/**
	 * @param string $style
	 * @param string $parent
	 */
	public function addStyle(string $style, string $parent = '#'): void {
		if (isset(Route::$styles[$style])) {
			return;
		}
		if ($parent !== NULL) {
			if (!isset(Route::$styles[$parent])) {
				throw new InvalidArgumentException("Parent style '$parent' doesn't exist.");
			}
			Route::$styles[$style] = Route::$styles[$parent];

		} else {
			Route::$styles[$style] = [];
		}
	}

	/**
	 * @param string $style
	 * @param string $key
	 * @param callable $value
	 */
	public function setStyleProperty(string $style, string $key, callable $value): void {
		if (!isset(Route::$styles[$style])) {
			throw new InvalidArgumentException("Style '$style' doesn't exist.");
		}
		Route::$styles[$style][$key] = $value;
	}

	/**
	 * @param string $module
	 * @param int $priority
	 * @return RouteList
	 */
	public function getModule(string $module, int $priority = 0): RouteList {
		$this->checkPriority($priority);
		if (!isset($this->modules[$module])) {
			throw new RouterException("Router module '$module' is not set. Please configure it.");
		}
		if ($this->modules[$module][$priority] === null) {
			$this->modules[$module][$priority] = new RouteList($module);
		}

		return $this->modules[$module][$priority];
	}

	protected function checkPriority(int $priority): void {
		if ($priority < 0 || $priority > 10) {
			throw new OutOfRangeException('Priority out of range.');
		}
	}

	/**
	 * @internal
	 * @param string $module
	 * @throws RouterException
	 */
	public function createModule(string $module): void {
		if ($this->createStage) {
			throw new RouterException('Cannot create module in router. Please create it in extension or config.');
		}

		$this->modules[$module] = array_fill(0, 11, null);
	}

	/**
	 * @return RouteList
	 * @throws RouterException
	 */
	public function createRouter(): RouteList {
		$this->createStage = true;

		foreach ($this->routers as $router) {
			if (!is_object($router)) {
				$router = new $router;
			}
			if (!$router instanceof IRouter) {
				throw new RouterException('Class ' . get_class($router) . ' must implements ' . IRouter::class);
			}
			$router->createRouter($this);
		}

		$return = new RouteList();
		foreach ($this->modules as $module => $values)	 {
			$routeList = !$module ? $return : new RouteList($module);
			/** @var RouteList|null $list */
			foreach ($values as $list) {
				if($list) {
					foreach ($list->getIterator() as $route) {
						$routeList[] = $route;
					}
				}
			}
			if ($routeList !== $return) {
				$return[] = $routeList;
			}
		}

		$this->createStage = false;

		return $return;
	}

	public function finish(): void {
		trigger_error('finish() is deprecated', E_USER_DEPRECATED);
	}

	/**
	 * @param array $forbiddenRouters
	 * @throws RouterException
	 */
	public function setForbiddenRouters(array $forbiddenRouters): void {
		trigger_error('setForbiddenRouters is deprecated', E_USER_DEPRECATED);
	}

}
