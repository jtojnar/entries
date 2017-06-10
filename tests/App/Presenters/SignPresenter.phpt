<?php

namespace Tests\App\Presenters;

require __DIR__ . '/../../bootstrap.php';

/**
 * @testCase
 */
class SignPresenterTest extends \Tester\TestCase {
	use \Testbench\TPresenter;

	public function testActionOut() {
		$this->checkRedirect('Sign:out', '/homepage/in');
	}

	public function testCreateComponentSignInForm() {
		$this->checkForm('Sign:', 'signInForm', [
			'teamid' => '###', //FIXME: replace with value
			'password' => '###', //FIXME: replace with value
			'remember' => false
		], false);
	}
}

(new SignPresenterTest())->run();
