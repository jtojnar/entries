<?php

declare(strict_types=1);

namespace App\Exporters;

interface IExporter {
	public function getMimeType(): string;

	public function output();
}
