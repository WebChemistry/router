# WebChemistry/router

## Overview

- [Installation](README.md#Installation)
- [Configuration](README.md#Configuration)
- [Main router](README.md#Main-router)
- [Use autoregistration in own extension](README.md#Usage-autoregistration-in-own-extension)

## Installation

Install via composer.

```sh
composer require webchemistry/router
```


neon:
```yaml
extensions:
    routers: WebChemistry\Routing\DI\RouterExtension
```

## Configuration
```yaml
routers:
    modules:
        - Front
        - Admin
    routers:
        - App\MainRouter
        - YourRouter
        - HisRouter
```

## Main router

```php
<?php

namespace App

class MainRouter implements WebChemistry\Routing\IRouter {

	/**
	 * @param RouteManager $routeManager
	 */
	public function createRouter(RouteManager $routeManager) {
		$routeManager->addStyle('name');
		$routeManager->setStyleProperty('name', Route::FILTER_OUT, function($url) {
			return Strings::webalize($url);
		});
		$routeManager->setStyleProperty('name', Route::FILTER_IN, function($url) {
			return Strings::webalize($url);
		});

		// Admin
		$admin = $routeManager->getModule('Admin');
		$admin[] = new Route('admin/<presenter>[/<action>][/<id [0-9]+>[-<name [0-9a-zA-Z\-]+>]]', [
			'presenter' => 'Homepage',
			'action' => 'default',
		]);

		// Front
		$front = $routeManager->getModule('Front');
		$front[] = new Route('<presenter>[/<action>][/<id [0-9]+>[-<name [0-9a-zA-Z\-]+>]]', [
			'presenter' => 'Homepage',
			'action' => 'default',
		]);
	}
	
}

```

## Usage autoregistration in own extension

```php
<?php

namespace TestPackage\Test\DI;

use Nette\Application\IPresenterFactory;
use Nette\DI\CompilerExtension;

class TestExtension extends CompilerExtension
{


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('routers'))
			->addTag('router')
			->setFactory(\TestPackage\Test\TestRouter::class)
			->setAutowired(true);

		$builder->getDefinition('routers.routerManager')
			->addSetup('createModule', ['Test']);
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$builder->getDefinition($builder->getByType(IPresenterFactory::class))
			->addSetup(
				'setMapping',
				[['Test' => 'TestPackage\Test\Presenters\*Presenter']]
		);
	}
}
```

```php
<?php

namespace TestPackage\Test;

use WebChemistry\Routing\IRouter;
use WebChemistry\Routing\RouteManager;
use Nette\Application\Routers\Route;

class TestRouter implements IRouter
{

	/**
	 * @param RouteManager $routeManager
	 */
	public function createRouter(RouteManager $routeManager): void
	{
		$app = $routeManager->getModule('Test');
		$app[] = new Route('/test/<presenter>/<action>[/<id>]', 'Default:default');
	}
}
```

app/config.neon:

```yaml
extensiona:

    ...
    - TestPackage\Test\DI\TestExtension
    ...
```

For correct router orders, you have to list all routers in app/config.neon:

```yaml
extensiona:
    ...
    routers: WebChemistry\Routing\DI\RouterExtension
    - TestPackage\Test\DI\TestExtension
    ...

routers:
    modules:
        ...
        - Test
        ...
        - App
    routers:
        ...
        - App\MainRouter
```
