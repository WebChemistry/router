<?php declare(strict_types = 1);

namespace Test;

use Nette\Application\Routers\RouteList;
use WebChemistry\Routing\IRouter;
use WebChemistry\Routing\RouteManager;

class RouteObject {

	public static function createRouter(array $options) {
		return new class ($options) implements IRouter {

			/** @var array */
			private $options;

			public function __construct(array $options) {
				$this->options = $options;
			}

			public function createRouter(RouteManager $manager): void {
				foreach ($this->options as $module => $option) {
					$module = $manager->getModule($module, $option['priority'] ?? 0);

					foreach ($option['routers'] as $router) {
						$module[] = new Route($router);
					}
				}
			}

		};
	}

	protected static function mergeModules(array &$ref, array $modules): void {
		foreach ($modules as $name) {
			$ref[$name] = $name;
		}
	}

	public static function create(array $options): RouteManager {
		$routers = [];
		$modules = [];
		foreach ($options as $option) {
			self::mergeModules($modules, array_keys($option));
			$routers[] = self::createRouter($option);
		}

		return new RouteManager($routers, $modules);
	}

	protected static function toArray($arg) {
		$result = [];

		if ($arg instanceof RouteList) {
			$result[$arg->getModule()] = [];
			foreach ($arg as $index => $value) {
				$result[$arg->getModule()][$index] = self::toArray($value);
			}
		} else if (is_iterable($arg)) {
			foreach ($arg as $index => $value) {
				$result[$index] = self::toArray($value);
			}
		} else if ($arg instanceof Route) {
			return $arg->getParam();
		} else {
			throw new \Exception('Error');
		}

		return $result;
	}

	public static function result(array $options) {
		$result = [];

		return current(self::toArray(self::create($options)->createRouter()));
	}

}
