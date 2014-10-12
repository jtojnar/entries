<?php

namespace App\Model;

class CountryModel extends BaseModel {
	public function getCountries() {
		return $this->db->table(self::TABLE_COUNTRY);
	}
	public function getCountriesPairs() {
		return $this->db->table(self::TABLE_COUNTRY)->order('name')->fetchPairs('id', 'name');
	}
}
