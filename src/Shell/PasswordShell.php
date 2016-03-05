<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\i18n\Time;
use Cake\ORM\TableRegistry;
use Cake\Auth\DefaultPasswordHasher;

class PasswordShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

	public function main() {
		$Users = TableRegistry::get($this->args[0]);
		$passwordHasher = new DefaultPasswordHasher();
		$primaryKey = $Users->primaryKey();
		$today = Time::now();

		while (true) {
			$query = $Users->find('list', ['keyField' => $primaryKey, 'valueField' => 'password'])
			->where([
				'password IS NOT NULL',
				'modified_user_id' => -9
			])
			->limit(1000)
			;

			$resultSet = $query->all();
			if ($resultSet->count() == 0) break;

			foreach ($resultSet as $id => $password) {
				$password = trim($password);
				if (!empty($password)) {
					$hash = $passwordHasher->hash($password);
					$Users->updateAll(
						['password' => $hash, 'modified_user_id' => 1, 'modified' => $today],
						[$primaryKey => $id]
					);
				}
			}
		}
	}
}
