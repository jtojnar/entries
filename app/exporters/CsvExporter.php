<?php

declare(strict_types=1);

namespace App\Exporters;

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
	private $teams;
	private $countries;
	private $teamFields;
	private $personFields;
	private $categoryFormat;
	private $maxMembers;

	public function __construct($teams, $countries, $teamFields, $personFields, \Closure $categoryFormat, int $maxMembers) {
		$this->teams = $teams;
		$this->countries = $countries;
		$this->teamFields = $teamFields;
		$this->personFields = $personFields;
		$this->categoryFormat = $categoryFormat;
		$this->maxMembers = $maxMembers;
	}

	public function getMimeType(): string {
		return 'text/csv';
	}

	public function output(): void {
		$fp = fopen('php://output', 'a');
		$headers = ['#', 'name', 'registered', 'category'];

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
			$row = [$team->id, $team->name, $team->timestamp, $this->categoryFormat->__invoke($team)];
			foreach ($this->teamFields as $name => $field) {
				$f = isset($team->getJsonData()->$name) ? $team->getJsonData()->$name : null;
				if ($f) {
					if ($field['type'] === 'country') {
						$row[] = $this->countries->getById($f)->name;
					} elseif ($field['type'] === 'checkboxlist') {
						foreach ($field['items'] as $itemKey => $item) {
							$row[] = \in_array($itemKey, $f, true);
						}
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

			$i = 0;
			$remaining = $this->maxMembers;
			foreach ($team->persons as $person) {
				++$i;
				$row[] = $person->lastname;
				$row[] = $person->firstname;
				$row[] = $person->gender;
				foreach ($this->personFields as $name => $field) {
					$f = isset($person->getJsonData()->$name) ? $person->getJsonData()->$name : null;
					if ($f) {
						if ($field['type'] === 'country') {
							$row[] = $this->countries->getById($f)->name;
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
						} elseif ($field['type'] === 'sportident') {
							$row[] = $f->cardId ?? '';
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
}
