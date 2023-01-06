<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Exception;

class InactiveRoleRemovalShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

 	public function main() {
		$this->out('Initialize Inactive Role Removal Shell ...');

		try {
			$model = TableRegistry::get('Institution.Staff');
			$model->removeInactiveStaffSecurityRole();

			$this->out('End Processing Inactive Role Removal');
		} catch (\Exception $e) {
			$this->out('Inactive Role Removal > Exception : ');
			$this->out($e->getMessage());
		}
	}
}
