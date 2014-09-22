<?php

namespace App\Model;

class CountryModel extends BaseModel {
	public function getCountries() {
		return $this->db->table('country');
	}
	public function getCountriesPairs() {
		return $this->db->table('country')->order('name')->fetchPairs('id', 'name');
	}
}
