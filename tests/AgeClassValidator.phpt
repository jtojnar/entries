<?php

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

$container = require __DIR__ . '/bootstrap.php';

class ExampleTest extends Tester\TestCase {
	private $container;

	public function __construct(Nette\DI\Container $container) {
		$this->container = $container;
	}

	public function testSample() {
		Assert::true(true);
	}
}

$test = new ExampleTest($container);
$test->run();
