<?php

namespace Test;

use Nette;
use Tester;
use Tester\Assert;
use App\Components\TeamForm;

$container = require __DIR__ . '/bootstrap.php';


class ExampleTest extends Tester\TestCase {
	private $container;

	private $classes;


	function __construct(Nette\DI\Container $container) {
		$this->container = $container;
	}


	function setUp() {
		$this->classes = [
			'youth' => ['max' => 23],
			'open' => [],
			'veteran' => ['min' => 40],
			'superveteran' => ['min' => 55],
			'ultraveteran' => ['min' => 65]
		];
	}


	function testConditionless() {
		$class = 'open';
		$ages = [12, 40, 75];
		Assert::true(TeamForm::validateAgeClass($class, $ages, $this->classes));
	}

	public function testMinInvalidFirst() {
		$class = 'veteran';
		$ages = [15, 55];
		Assert::false(TeamForm::validateAgeClass($class, $ages, $this->classes));
	}

	public function testMinInvalidLast() {
		$class = 'veteran';
		$ages = [55, 17];
		Assert::false(TeamForm::validateAgeClass($class, $ages, $this->classes));
	}

	public function testMinBorderline() {
		$class = 'veteran';
		$ages = [40, 41];
		Assert::true(TeamForm::validateAgeClass($class, $ages, $this->classes));
	}

	public function testMaxInvalidFirst() {
		$class = 'youth';
		$ages = [55, 15];
		Assert::false(TeamForm::validateAgeClass($class, $ages, $this->classes));
	}

	public function testMaxInvalidLast() {
		$class = 'youth';
		$ages = [17, 55];
		Assert::false(TeamForm::validateAgeClass($class, $ages, $this->classes));
	}

	public function testMaxBorderline() {
		$class = 'youth';
		$ages = [22, 23];
		Assert::true(TeamForm::validateAgeClass($class, $ages, $this->classes));
	}
}


$test = new ExampleTest($container);
$test->run();
