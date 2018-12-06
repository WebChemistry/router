<?php declare(strict_types=1);

namespace WebChemistry\Routing\DI;

use Nette\DI\CompilerExtension;
use WebChemistry\Routing\RouteManager;
use Nette\Application\IRouter;
use WebChemistry\Routing\RouterException;
use WebChemistry\Routing;

class RouterExtension extends CompilerExtension {

	const ROUTER_TAG = 'router';

	/** @var array */
	public $defaults = [
		'routers' => [],
		'main' => null,
	];

	/** @var bool */
	private $fixed = false;

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 *
	 * @throws RouterException
	 */
	public function loadConfiguration(): void {
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		if (!$config['main']) {
			throw new RouterException('Main route must be set.');
		}

		$this->checkRouter($config['main']);
		$builder->addDefinition($this->prefix('mainRouter'))
			->setType(Routing\IRouter::class)
			->setFactory($config['main']);

		$routers = [];
		foreach ($config['routers'] as $name => $router) {
			$this->checkRouter($router);
			$routers[] = $builder->addDefinition($this->prefix('router.' . $name))
				->setType(Routing\IRouter::class)
				->setFactory($router)
				->setAutowired(false);
		}

		$builder->addDefinition($this->prefix('routerManager'))
			->setFactory(RouteManager::class, [$this->prefix('@mainRouter'), $routers]);

		// kdyby/console fix
		if ($serviceName = $builder->getByType(IRouter::class)) {
			$this->fixed = true;
			$builder->getDefinition($serviceName)
				->setFactory('@' . RouteManager::class . '::createRouter');
		}
	}

	/**
	 * Adjusts DI container before is compiled to PHP class. Intended to be overridden by descendant.
	 *
	 * @return void
	 */
	public function beforeCompile(): void {
		$builder = $this->getContainerBuilder();
		foreach ($builder->findByTag(self::ROUTER_TAG) as $name => $attrs) {
			$builder->getDefinition($this->prefix('routerManager'))
				->addSetup('addRouter', [$builder->getDefinition($name)]);
		}
		if ($this->fixed) {
			return;
		}

		$builder->getDefinition('router')
			->setFactory('@' . RouteManager::class . '::createRouter');
	}

	private function checkRouter(string $class): void {
		if (!class_exists($class)) {
			throw new RouterException("Router $class not exists.");
		}
	}

}
