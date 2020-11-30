<?php

declare(strict_types=1);

namespace App\Exporters;

use App;
use App\Templates\Filters\CategoryFormatFilter;
use Nextras\Orm\Collection\ICollection;

/**
 * Legacy CSV Exporter.
 *
 * The output is generic CSV file – values separated by commas. The first line
 * contains heading (comma separated list of column names).
 *
 * Each following row represents one team. First four columns are id, team name,
 * date the team was registered and category. Those are followed by arbitrary
 * number of columns as configured by user. The event sets the maximum number
 * of team members. For each of these possible team member there are at least
 * four columns: last name, first name, gender and birth date. Between the birth
 * date and gender, there can be also arbitrary amount of custom columns
 * (as configured by user). The last column contains the status of the team
 * (i.e. either registered, or paid).
 */
class CsvExporter implements IExporter {
	/** @var ICollection */
	private $teams;

	/** @var App\Model\CountryRepository */
	private $countries;

	/** @var array */
	private $teamFields;

	/** @var array */
	private $personFields;

	/** @var CategoryFormatFilter */
	private $categoryFormatter;

	/** @var int */
	private $maxMembers;

	public function __construct(ICollection $teams, App\Model\CountryRepository $countries, array $teamFields, array $personFields, CategoryFormatFilter $categoryFormatter, int $maxMembers) {
		$this->teams = $teams;
		$this->countries = $countries;
		$this->teamFields = $teamFields;
		$this->personFields = $personFields;
		$this->categoryFormatter = $categoryFormatter;
		$this->maxMembers = $maxMembers;
	}

	public function getMimeType(): string {
		return 'text/csv';
	}

	public function output(): void {
		$fp = fopen('php://output', 'a');
		if ($fp === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$headers = ['#', 'name', 'registered', 'category', 'message'];

		foreach ($this->teamFields as $name => $field) {
			if ($field['type'] === 'checkboxlist') {
				foreach ($field['items'] as $itemKey => $_) {
					$headers[] = $name . '-' . $itemKey;
				}
			} else {
				$headers[] = $name;
			}
		}

		for ($i = 1; $i <= $this->maxMembers; ++$i) {
			$headers[] = 'm' . $i . 'lastname';
			$headers[] = 'm' . $i . 'firstname';
			$headers[] = 'm' . $i . 'email';
			$headers[] = 'm' . $i . 'gender';
			foreach ($this->personFields as $name => $field) {
				if ($field['type'] === 'checkboxlist') {
					foreach ($field['items'] as $itemKey => $_) {
						$headers[] = 'm' . $i . $name . '-' . $itemKey;
					}
				} else {
					$headers[] = 'm' . $i . $name;
				}
			}
			$headers[] = 'm' . $i . 'birth';
		}

		$headers[] = 'status';
		fputcsv($fp, $headers);

		foreach ($this->teams as $team) {
			$row = [$team->id, $team->name, $team->timestamp, $this->categoryFormatter->__invoke($team), $team->message];
			$row = $this->addCustomFields($row, $this->teamFields, $team);
			$i = 0;
			$remaining = $this->maxMembers;
			foreach ($team->persons as $person) {
				++$i;
				$row[] = $person->lastname;
				$row[] = $person->firstname;
				$row[] = $person->email;
				$row[] = $person->gender;
				$row = $this->addCustomFields($row, $this->personFields, $person);
				$row[] = $person->birth;
				--$remaining;
			}
			if ($remaining > 0) {
				for ($i = 0; $i < $remaining; ++$i) {
					$row[] = '';
					$row[] = '';
					$row[] = '';
					foreach ($this->personFields as $name => $field) {
						if ($field['type'] === 'checkboxlist') {
							foreach ($field['items'] as $item) {
								$row[] = '';
							}
						} else {
							$row[] = '';
						}
					}
					$row[] = '';
				}
			}
			$row[] = $team->status;
			fputcsv($fp, $row);
		}
		fclose($fp);
		exit;
	}

	/**
	 * @param App\Model\Team|App\Model\Person $container
	 */
	private function addCustomFields(array $row, array $fields, $container): array {
		foreach ($fields as $name => $field) {
			$f = isset($container->getJsonData()->$name) ? $container->getJsonData()->$name : null;
			if ($f) {
				if ($field['type'] === 'country') {
					// TODO: https://github.com/nextras/orm/issues/319
					/** @var App\Model\Country */
					$country = $this->countries->getById($f);
					$row[] = $country->name;
				} elseif ($field['type'] === 'checkboxlist') {
					foreach ($field['items'] as $itemKey => $_) {
						$row[] = \in_array($itemKey, $f, true);
					}
				} elseif ($field['type'] === 'sportident') {
					$row[] = $f->cardId ?? 'rent';
				} else {
					$row[] = $f;
				}
			} else {
				if ($field['type'] === 'checkboxlist') {
					foreach ($field['items'] as $item) {
						$row[] = '';
					}
				} else {
					$row[] = '';
				}
			}
		}

		return $row;
	}
}
