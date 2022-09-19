<?php

declare(strict_types=1);

namespace App\Helpers;

use Exception;
use SplFileObject;

class CsvWriter {
	/**
	 * @var string[]
	 */
	private array $columns = [];

	/**
	 * @param SplFileObject $file handle to write the CSV file to
	 */
	public function __construct(
		private SplFileObject $file,
	) {
	}

	/**
	 * Declare columns.
	 *
	 * @param string|string[] $columns
	 */
	public function addColumns(string|array $columns): void {
		if (\is_string($columns)) {
			$columns = [$columns];
		}

		$this->columns = array_merge($this->columns, $columns);
	}

	/**
	 * Write headers.
	 */
	public function writeHeaders(): void {
		$this->file->fputcsv($this->columns);
	}

	/**
	 * Write line to the CSV file.
	 *
	 * @param array $data
	 */
	public function write($data): void {
		foreach (array_keys($data) as $key) {
			if (!\in_array($key, $this->columns, true)) {
				throw new Exception("Unknown column name “{$key}”.");
			}
		}

		$result = $this->file->fputcsv(
			array_map(
				fn($column) => $data[$column] ?? '',
				$this->columns
			)
		);

		if ($result === false) {
			throw new Exception('Error writing in CSV file.');
		}
	}
}
