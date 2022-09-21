<?php

declare(strict_types=1);

namespace App\Model;

use Nette\ComponentModel\IContainer;
use Nette\Forms\Control;

interface InputModifier {
	public static function modify(Control $input, IContainer $container): void;
}
