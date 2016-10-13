<?php

namespace App\Exporters;

use Nette\Utils\Strings;

/**
 * Exporter to MeOS format or something
 *
 * The output consists of team rows and person rows. Each team row is followed
 * by rows of its members (usually two to three). Each row contains values
 * separated by semicolons. It is assumed no value contains semicolon.
 *
 * Team row contains these values: category, team name, club
 * Person row contains these values: full name, sportident card, club, category
 */
class MeosExporter implements IExporter {
	const DELIMITER = ';';

	private $fp;
	private $teams;
	private $categoryFormat;

	function __construct($teams, \Closure $categoryFormat) {
		$this->teams = $teams;
		$this->categoryFormat = $categoryFormat;
	}

	public function getMimeType() : string {
		return 'text/plain';
	}

	private function outputRow($row) {
		// fPutCsv($this->fp, $row, self::DELIMITER);
		echo Strings::toAscii(implode(self::DELIMITER, $row)) . PHP_EOL;
	}

	public function output() {
		$this->fp = fOpen('php://output', 'a');
		foreach ($this->teams as $team) {
			$category = $this->categoryFormat->__invoke($team);
			$club = '';
			$this->outputRow([$category, $team->name, $club]);

			foreach ($team->persons as $person) {
				$additionalData = $person->getJsonData();
				$fullName = $person->firstname . ' ' . $person->lastname;
				$sportident = isset($additionalData->sportident) && $additionalData->sportident !== null ? $additionalData->sportident : '';
				$club = '';
				$this->outputRow([$fullName, $sportident, $club, $category]);
			}
		}
		fClose($this->fp);
		exit;
	}
}
