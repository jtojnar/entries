<?php

namespace App\Exporters;

interface IExporter {
	public function getMimeType() : string;
	public function output();
}
