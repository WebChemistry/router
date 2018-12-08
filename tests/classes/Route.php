<?php declare(strict_types = 1);

namespace Test;

use Nette;
use Nette\Application\IRouter;
use Nette\Application\Request;

class Route implements IRouter {

	/** @var string|null */
	private $param;

	public function __construct(?string $param) {
		$this->param = $param;
	}

	/**
	 * @return string|null
	 */
	public function getParam(): ?string {
		return $this->param;
	}

	public function match(Nette\Http\IRequest $httpRequest) {

	}

	public function constructUrl(Request $appRequest, Nette\Http\Url $refUrl) {

	}

}
