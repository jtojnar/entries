<?php

declare(strict_types=1);

namespace App\Exporters;

use App\Model\Team;
use App\Templates\Filters\CategoryFormatFilter;
use Nette\Utils\Strings;
use Nextras\Orm\Collection\ICollection;

/**
 * Exporter to MeOS format or something.
 *
 * The output consists of team rows and person rows. Each team row is followed
 * by rows of its members (usually two to three). Each row contains values
 * separated by semicolons. It is assumed no value contains semicolon.
 *
 * Team row contains these values: category, team name, club
 * Person row contains these values: full name, sportident card, club, category
 */
class MeosExporter implements IExporter {
	public const DELIMITER = ';';

	/** @var Team[]|ICollection */
	private $teams;

	/** @var CategoryFormatFilter */
	private $categoryFormatter;

	/**
	 * @param Team[]|ICollection $teams
	 */
	public function __construct(ICollection $teams, CategoryFormatFilter $categoryFormatter) {
		$this->teams = $teams;
		$this->categoryFormatter = $categoryFormatter;
	}

	public function getMimeType(): string {
		return 'text/plain';
	}

	/**
	 * @param resource $fp
	 */
	private function outputRow($fp, array $row): void {
		fwrite($fp, Strings::toAscii(implode(self::DELIMITER, $row)) . PHP_EOL);
	}

	public function output(): void {
		$fp = fopen('php://output', 'a');
		if ($fp === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		foreach ($this->teams as $team) {
			$category = $this->categoryFormatter->__invoke($team);
			$club = '';
			$this->outputRow($fp, [$category, $team->name, $club]);

			foreach ($team->persons as $person) {
				$additionalData = $person->getJsonData();
				$fullName = $person->lastname . ' ' . $person->firstname;
				$sportident = $additionalData->sportident->cardId ?? '';
				$club = '';
				$this->outputRow($fp, [$fullName, $sportident, $club, $category]);
			}
		}
		fclose($fp);
		exit;
	}
}
