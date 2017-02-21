<?php

namespace App\Exporters;

/**
 * Legacy CSV Exporter
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

	public function output() {
		$fp = fopen('php://output', 'a');
		$headers = array('#', 'name', 'registered', 'category');

		foreach ($this->teamFields as $name => $field) {
			$headers[] = $name;
		}

		for ($i = 1; $i <= $this->maxMembers; ++$i) {
			$headers[] = 'm' . $i . 'lastname';
			$headers[] = 'm' . $i . 'firstname';
			$headers[] = 'm' . $i . 'gender';
			foreach ($this->personFields as $name => $field) {
				$headers[] = 'm' . $i . $name;
			}
			$headers[] = 'm' . $i . 'birth';
		}

		$headers[] = 'status';
		fputcsv($fp, $headers);

		foreach ($this->teams as $team) {
			$row = array($team->id, $team->name, $team->timestamp, $this->categoryFormat->__invoke($team));
			foreach ($this->teamFields as $name => $field) {
				$f = isset($team->getJsonData()->$name) ? $team->getJsonData()->$name : null;
				if ($f) {
					if ($field['type'] === 'country') {
						$row[] = $this->countries->getById($f)->name;
					} else {
						$row[] = $f;
					}
				} else {
					$row[] = '';
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
						} else {
							$row[] = $f;
						}
					} else {
						$row[] = '';
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
						$row[] = '';
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
