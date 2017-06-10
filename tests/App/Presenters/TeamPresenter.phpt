<?php

namespace Tests\App\Presenters;

require __DIR__ . '/../../bootstrap.php';

/**
 * @testCase
 */
class TeamPresenterTest extends \Tester\TestCase {
	use \Testbench\TPresenter;

	public function testRenderList() {
		$this->checkAction('Team:list');
	}

	public function testRenderEdit() {
		$this->checkRedirect('Team:edit', '/sign/in');
	}

	public function testActionConfirm() {
		//FIXME: parameters may not be correct
		$this->checkAction('Team:confirm', ['id' => null]);

		$this->checkAction('Team:confirm', []);
	}

	public function testActionExport() {
		$this->checkRedirect('Team:export', '/sign/in');
	}

	public function testCreateComponentTeamForm() {
		$this->checkForm('Team:', 'teamForm', [
			'name' => '###', //FIXME: replace with value
			'category' => '###', //FIXME: replace with value
			'sportident' => '###', //FIXME: replace with value
			'sportidentNeeded' => false,
			'friday2h' => '###', //FIXME: replace with value
			'saturday5h' => '###', //FIXME: replace with value
			'sunday4h' => '###', //FIXME: replace with value
			'message' => '###', //FIXME: replace with value
			'firstname' => '###', //FIXME: replace with value
			'lastname' => '###', //FIXME: replace with value
			'gender' => '###', //FIXME: replace with value
			'country' => '###', //FIXME: replace with value
			'boarding' => '###', //FIXME: replace with value
			'email' => '###', //FIXME: replace with value
			'birth' => '###', //FIXME: replace with value
			'firstname' => '###', //FIXME: replace with value
			'lastname' => '###', //FIXME: replace with value
			'gender' => '###', //FIXME: replace with value
			'country' => '###', //FIXME: replace with value
			'boarding' => '###', //FIXME: replace with value
			'email' => '###', //FIXME: replace with value
			'birth' => '###', //FIXME: replace with value
		], false);
	}

	public function testCreateComponentTeamListFilterForm() {
		$this->checkForm('Team:', 'teamListFilterForm', [
			'category' => '###', //FIXME: replace with value
		], false);
	}
}

(new TeamPresenterTest())->run();
