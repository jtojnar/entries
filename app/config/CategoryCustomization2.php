<?php

namespace App;

use Nette;

class CategoryCustomization2 extends Nette\Object {
	public static function detectCategory(Model\Team $team, $presenter) {
		if ($team->ageclass == 'newcomers') {
			return 'PO';
		} else if ($team->ageclass == 'newcomerb') {
			return 'PZ';
		}

		$gender = $team->genderclass;
		$age = $team->ageclass;
		$ages_data = $presenter->context->parameters['entries']['categories']['age'];
		if (count($ages_data) > 1) {
			$age = isset($ages_data[$age]) ? $ages_data[$age]['short'] : '?';
		} else {
			$age = '';
		}

		$gender_data = $presenter->context->parameters['entries']['categories']['gender'];
		if (count($gender_data) > 1) {
			$gender = isset($gender_data[$gender]) ? $gender_data[$gender]['short'] : '?';
		} else {
			$gender = '';
		}
		return $gender . $age;
	}

	public static function getCategories() {
		return ['DD20' => ['ageclass' => 'junior', 'genderclass' => 'female'], 'HH20' => ['ageclass' => 'junior', 'genderclass' => 'male'], 'HD20' => ['ageclass' => 'junior', 'genderclass' => 'mixed'], 'DD' => ['ageclass' => 'open', 'genderclass' => 'female'], 'HH' => ['ageclass' => 'open', 'genderclass' => 'male'], 'HD' => ['ageclass' => 'open', 'genderclass' => 'mixed'], 'DD40' => ['ageclass' => 'veteran', 'genderclass' => 'female'], 'HH40' => ['ageclass' => 'veteran', 'genderclass' => 'male'], 'HD40' => ['ageclass' => 'veteran', 'genderclass' => 'mixed'], 'PO' => ['ageclass' => 'newcomers'], 'PZ' => ['ageclass' => 'newcomerb']];
	}
}
