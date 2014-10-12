<?php

namespace App\Model;

use Nette\Security\Passwords;

class TeamModel extends BaseModel {
	public function addTeam($teamdata, $members) {
		$teamdata['password'] = Passwords::hash($teamdata['password']);
		$team = $this->db->table(self::TABLE_TEAM)->insert($teamdata);
		foreach ($members as $member) {
			$this->addPerson($team, $member);
		}
		return $team;
	}

	public function updateTeam($teamid, $teamdata, $members, $newmembers) {
		$team = $this->db->table(self::TABLE_TEAM)->wherePrimary($teamid)->update($teamdata);
		foreach ($members as $key => $member) {
			if ($member === null) {
				$this->deletePerson($key);
			} else {
				$this->updatePerson($key, $member);
			}
		}
		foreach ($newmembers as $member) {
			$this->addPerson($teamid, $member);
		}
		return $team;
	}

	public function getTeams() {
		return $this->db->table(self::TABLE_TEAM);
	}

	public function updateStatus($teamid, $status) {
		$team = $this->db->table(self::TABLE_TEAM)->wherePrimary($teamid)->update(array('status' => $status));
	}

	public function getStatus($teamid) {
		$status = $this->getTeam($teamid);
		return $status['status'];
	}

	/**
	* @deprecated
	*/
	public function getPersons($teamid) {
		return $this->getTeamMembers($teamid);
	}

	public function getTeamMembers($teamid) {
		return $this->db->table(self::TABLE_PERSON)->where('team_id = ?', $teamid);
	}

	public function getTeam($id) {
		return $this->db->table(self::TABLE_TEAM)->wherePrimary($id)->fetch();
	}

	public function getPersonsIds($id) {
		return array_values($this->db->table(self::TABLE_PERSON)->where('team_id = ?', $id)->fetchPairs('id', 'id'));
	}

	public function generatePassword() {
		return \Nette\Utils\Strings::random();
	}
	
	public function addPerson($teamid, $data) {
		$data['team_id'] = $teamid;
		$this->db->table(self::TABLE_PERSON)->insert($data);
	}

	public function updatePerson($id, $data) {
		$this->db->table(self::TABLE_PERSON)->wherePrimary($id)->update($data);
	}

	public function deletePerson($id) {
		$this->db->table(self::TABLE_PERSON)->wherePrimary($id)->delete();
	}

	public function personExists($id) {
		return $this->db->table(self::TABLE_PERSON)->wherePrimary($id)->count() > 0;
	}

	public function beginTransaction() {
		return $this->db->beginTransaction();
	}

	public function commit() {
		return $this->db->commit();
	}
}
