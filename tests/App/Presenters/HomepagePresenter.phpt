<?php

namespace Tests\App\Presenters;

require __DIR__ . '/../../bootstrap.php';

/**
 * @testCase
 */
class HomepagePresenterTest extends \Tester\TestCase {
	use \Testbench\TPresenter;

	public function testRenderDefault() {
		$this->checkAction('Homepage:default');
	}
}

(new HomepagePresenterTest())->run();
