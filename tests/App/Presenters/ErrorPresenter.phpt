<?php

namespace Tests\App\Presenters;

use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

/**
 * @testCase
 */
class ErrorPresenterTest extends \Tester\TestCase {
	use \Testbench\TPresenter;

	public function testRenderDefault() {
		Assert::exception(function() {
			$this->checkAction('Error:default');
		}, 'Nette\Application\BadRequestException');
		Assert::same(404, $this->getReturnCode());
	}
}

(new ErrorPresenterTest())->run();
