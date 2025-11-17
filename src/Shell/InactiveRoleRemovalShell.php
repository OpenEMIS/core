<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Exception;

class InactiveRoleRemovalShell extends Shell {
	public function initialize(): void {
		parent::initialize();
	}

 	public function main() {
		$this->out('Initialize Inactive Role Removal Shell ...');

		try {
			$model = TableRegistry::getTableLocator()->get('Institution.Staff');
			$model->removeInactiveStaffSecurityRole();

			$this->out('End Processing Inactive Role Removal');
		} catch (\Exception $e) {
			$this->out('Inactive Role Removal > Exception : ');
			$this->out($e->getMessage());
		}
	}
}
