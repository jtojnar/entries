<?php

namespace App;

use Nette;

class CategoryCustomization extends Nette\Object {
	public static function detectCategory(Model\Team $team, $presenter) {
		$members = $team->persons->get()->fetchAll();
		if (count($members) === 1) {
			if ($members[0]->gender === 'female') {
				return 'D';
			} else {
				return 'M';
			}
		} else {
			$eventDate = $presenter->context->parameters['entries']['eventDate'];
			$plus = (($members[0]->birth->diff($eventDate, true)->y + $members[1]->birth->diff($eventDate, true)->y) > 80) && ($members[0]->birth->diff($eventDate, true)->y >= 35) && ($members[1]->birth->diff($eventDate, true)->y >= 35) ? '+' : '';
			if ($members[0]->gender === 'female' && $members[1]->gender === 'female') {
				return 'DD' . $plus;
			} elseif ($members[0]->gender === 'male' && $members[1]->gender === 'male') {
				return 'MM' . $plus;
			} else {
				return 'MD' . $plus;
			}
		}
	}

	public static function getCategories() {
		return ['D' => [], 'M' => [], 'DD' => [], 'MM' => [], 'MD' => [], 'DD+' => [], 'MM+' => [], 'MD+' => []];
	}
}
