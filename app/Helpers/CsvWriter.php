<?php

declare(strict_types=1);

namespace App\Helpers;

use Exception;

class CsvWriter {
	/**
	 * @var resource
	 */
	private $handle;

	/**
	 * @var string[]
	 */
	private $columns = [];

	/**
	 * @param resource $handle handle of file to write the CSV file to
	 */
	public function __construct($handle) {
		$this->handle = $handle;
	}

	/**
	 * Declare columns.
	 *
	 * @param string|string[] $columns
	 */
	public function addColumns($columns): void {
		if (\is_string($columns)) {
			$columns = [$columns];
		}

		$this->columns = array_merge($this->columns, $columns);
	}

	/**
	 * Write headers.
	 */
	public function writeHeaders(): void {
		fputcsv($this->handle, $this->columns);
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

		$result = fputcsv($this->handle, array_map(function($column) use ($data) {
			return $data[$column] ?? '';
		}, $this->columns));

		if ($result === false) {
			throw new Exception('Error writing in CSV file.');
		}
	}
}
