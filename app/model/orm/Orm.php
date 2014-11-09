<?php

namespace App\Model;

use Nextras\Orm\Model\DIModel;
use Nette\Caching\IStorage;
use Nette\DI\Container;

/**
 * Model
 * @property-read PersonRepository $persons
 * @property-read TeamRepository $teams
 * @property-read countryRepository $countries
 */
class Orm extends DIModel {
	/** @var string */
	public $prefix;

	public function __construct(Container $container, IStorage $cacheStorage, array $repositories) {
		parent::__construct($container, $cacheStorage, $repositories);
		$this->prefix = $container->parameters['database']['prefix'];
	}
}
