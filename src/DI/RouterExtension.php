<?php

namespace WebChemistry\Routing\DI;

use Nette;
use Nette\DI\CompilerExtension;
use WebChemistry\Routing\RouteManager;
use Nette\Application\IRouter;
use WebChemistry\Routing\RouterException;

class RouterExtension extends CompilerExtension {

	/** @var array */
	private $defaults = [
		'routers' => [],
		'main' => NULL
	];

	/** @var bool */
	private $fixed = FALSE;

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 *
	 * @throws RouterException
	 */
	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		if (!$config['main']) {
			throw new RouterException('Main route must be set.');
		}

		$builder->addDefinition($this->prefix('routerManager'))
			->setClass(RouteManager::class, [$config['main'], $config['routers']]);

		// kdyby/console fix
		if ($serviceName = $builder->getByType(IRouter::class)) {
			$this->fixed = TRUE;
			$builder->getDefinition($serviceName)
				->setFactory('@' . RouteManager::class . '::createRouter');
		}
	}

	/**
	 * Adjusts DI container before is compiled to PHP class. Intended to be overridden by descendant.
	 *
	 * @return void
	 */
	public function beforeCompile() {
		if ($this->fixed) {
			return;
		}
		$builder = $this->getContainerBuilder();

		$builder->getDefinition('router')
			->setFactory('@' . RouteManager::class . '::createRouter');
	}

}
