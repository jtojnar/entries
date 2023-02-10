<?php

declare(strict_types=1);

namespace App\Exporters;

use App;
use App\Helpers\CsvWriter;
use App\Helpers\Iter;
use App\Model\Configuration\Fields;
use App\Model\Orm\Person\Person;
use App\Model\Orm\Team\Team;
use App\Templates\Filters\CategoryFormatFilter;
use Nextras\Orm\Collection\ICollection;
use SplFileObject;

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
final class CsvExporter implements IExporter {
	public function __construct(
		/** @var ICollection<Team> $teams */
		private readonly ICollection $teams,
		private readonly App\Model\Orm\Country\CountryRepository $countries,
		/** @var array<Fields\Field> $teamFields */
		private readonly array $teamFields,
		/** @var array<Fields\Field> $personFields */
		private readonly array $personFields,
		private readonly CategoryFormatFilter $categoryFormatter,
	) {
	}

	public function getMimeType(): string {
		return 'text/csv';
	}

	public function output(): void {
		$file = new SplFileObject('php://output', 'a');
		$writer = new CsvWriter($file);
		$writer->addColumns(['#', 'name', 'registered', 'category', 'message']);
		$maxMembers = Iter::reduce(
			$this->teams,
			static fn(int $maximum, Team $team): int => max($maximum, $team->persons->count()),
			0,
		);

		foreach ($this->teamFields as $field) {
			if ($field instanceof Fields\CheckboxlistField) {
				$writer->addColumns(
					array_map(
						fn(string $itemKey): string => $field->name . '-' . $itemKey,
						array_keys($field->items),
					)
				);
			} else {
				$writer->addColumns($field->name);
			}
		}

		for ($i = 1; $i <= $maxMembers; ++$i) {
			$writer->addColumns([
				'm' . $i . 'lastname',
				'm' . $i . 'firstname',
				'm' . $i . 'email',
				'm' . $i . 'gender',
			]);
			foreach ($this->personFields as $field) {
				if ($field instanceof Fields\CheckboxlistField) {
					$writer->addColumns(
						array_map(
							fn(string $itemKey): string => 'm' . $i . $field->name . '-' . $itemKey,
							array_keys($field->items),
						)
					);
				} else {
					$writer->addColumns('m' . $i . $field->name);
				}
			}
			$writer->addColumns('m' . $i . 'birth');
		}

		$writer->addColumns('status');
		$writer->writeHeaders();

		foreach ($this->teams as $team) {
			$row = [
				'#' => $team->id,
				'name' => $team->name,
				'registered' => $team->timestamp,
				'category' => $this->categoryFormatter->__invoke($team),
				'message' => $team->message,
			];
			$row += $this->addCustomFields($this->teamFields, $team);
			$i = 0;
			foreach ($team->persons as $person) {
				++$i;
				$row['m' . $i . 'lastname'] = $person->lastname;
				$row['m' . $i . 'firstname'] = $person->firstname;
				$row['m' . $i . 'email'] = $person->email;
				$row['m' . $i . 'gender'] = $person->gender;
				$row += $this->addCustomFields($this->personFields, $person, 'm' . $i);
				$row['m' . $i . 'birth'] = $person->birth;
			}
			$row['status'] = $team->status;
			$writer->write($row);
		}
		exit;
	}

	/**
	 * @param array<Fields\Field> $fields
	 */
	private function addCustomFields(
		array $fields,
		Person|Team $container,
		string $prefix = '',
	): array {
		$row = [];
		foreach ($fields as $field) {
			$name = $field->name;
			$f = $container->getJsonData()->$name ?? null;
			if ($f) {
				if ($field instanceof Fields\CountryField) {
					$country = $this->countries->getByIdChecked($f);
					$row[$prefix . $name] = $country->codeIoc;
				} elseif ($field instanceof Fields\CheckboxlistField) {
					foreach ($field->items as $itemKey => $_) {
						$row[$prefix . $name . '-' . $itemKey] = \in_array($itemKey, $f, true);
					}
				} elseif ($field instanceof Fields\SportidentField) {
					$row[$prefix . $name] = $f->cardId ?? 'rent';
				} else {
					$row[$prefix . $name] = $f;
				}
			}
		}

		return $row;
	}
}
