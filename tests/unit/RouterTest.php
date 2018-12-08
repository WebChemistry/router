<?php declare(strict_types = 1);

use Test\RouteObject;

class RouterTest extends \Codeception\Test\Unit {

	protected function _before() {
	}

	protected function _after() {
	}

	public function testMainRouter() {
		$this->assertSame([
			['Front:' => ['foo', 'bar']],
		], RouteObject::result([
			[
				'Front' => [
					'routers' => [
						'foo',
						'bar',
					]
				]
			]
		]));
	}

	public function testRouters() {
		$this->assertSame([
			['Front:' => ['foo', 'bar', 'foo1']],
			['Admin:' => ['foo', 'bar']],
		], RouteObject::result([
			[
				'Front' => [
					'routers' => [
						'foo',
						'bar',
					]
				],
				'Admin' => [
					'routers' => [
						'foo',
						'bar',
					]
				]
			],
			[
				'Front' => [
					'routers' => [
						'foo1',
					]
				]
			]
		]));
	}

	public function testPriority() {
		$this->assertSame([
			['Front:' => ['foo', 'bar', 'foo1']],
			['Admin:' => ['foo', 'bar']],
		], RouteObject::result([
			[
				'Front' => [
					'priority' => 1,
					'routers' => [
						'foo',
						'bar',
					]
				],
				'Admin' => [
					'routers' => [
						'foo',
						'bar',
					]
				]
			],
			[
				'Front' => [
					'priority' => 5,
					'routers' => [
						'foo1',
					]
				]
			]
		]));
	}

}
