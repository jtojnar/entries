<?php

namespace App\Model;

use Nextras\Orm\Model\DIModel;

/**
 * Model
 * @property-read PersonRepository $persons
 * @property-read TeamRepository $teams
 * @property-read countryRepository $countries
 */
class Orm extends DIModel {
}
