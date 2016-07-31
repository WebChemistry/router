<?php

namespace WebChemistry\Routing\DI;

use Nette;
use Nette\DI\CompilerExtension;

class RouterExtension extends CompilerExtension {

	/** @var array */
	private $defaults = [
		'routers' => [],
		'main' => 'App\Routers\LocalRouter'
	];

	/** @var bool */
	private $fixed = FALSE;

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 *
	 * @return void
	 */
	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		$builder->addDefinition($this->prefix('routerManager'))
			->setClass('WebChemistry\Routing\RouteManager', [$config['main'], $config['routers']]);

		// kdyby/console fix
		if ($serviceName = $builder->getByType('Nette\Application\IRouter')) {
			$this->fixed = TRUE;
			$builder->getDefinition($serviceName)
				->setFactory('@WebChemistry\Routing\RouteManager::createRouter');
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
			->setFactory('@WebChemistry\Routing\RouteManager::createRouter');
	}

}
