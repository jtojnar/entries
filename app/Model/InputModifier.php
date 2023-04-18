<?php

declare(strict_types=1);

namespace App\Model;

use Nette\ComponentModel\IContainer;
use Nette\Forms\Control;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Rules;

interface InputModifier {
	/**
	 * @param callable(BaseControl): (BaseControl|Rules) $whenNotPlaceholder
	 */
	public static function modify(Control $input, IContainer $container, callable $whenNotPlaceholder): void;
}
