<?php

declare(strict_types=1);

namespace App\Exporters;

use App\Model\Team;
use App\Templates\Filters\CategoryFormatFilter;
use Nette\Utils\Strings;
use Nextras\Orm\Collection\ICollection;
use SplFileObject;

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
final class MeosExporter implements IExporter {
	public const DELIMITER = ';';

	public function __construct(
		/** @var ICollection<Team> */
		private ICollection $teams,
		private CategoryFormatFilter $categoryFormatter,
	) {
	}

	public function getMimeType(): string {
		return 'text/plain';
	}

	private function outputRow(SplFileObject $file, array $row): void {
		$file->fwrite(Strings::toAscii(implode(self::DELIMITER, $row)) . \PHP_EOL);
	}

	public function output(): void {
		$file = new SplFileObject('php://output', 'a');
		foreach ($this->teams as $team) {
			$category = $this->categoryFormatter->__invoke($team);
			$club = '';
			$this->outputRow($file, [$category, $team->name, $club]);

			foreach ($team->persons as $person) {
				$additionalData = $person->getJsonData();
				$fullName = $person->lastname . ' ' . $person->firstname;
				$sportident = $additionalData->sportident->cardId ?? '';
				$club = '';
				$this->outputRow($file, [$fullName, $sportident, $club, $category]);
			}
		}
		exit;
	}
}
