<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Exception;

class UpdateStaffStatusShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

 	public function main() {
		$this->out('Initialize Update Staff Status Shell ...');

		try {
			$model = TableRegistry::get('Institution.StaffTransferApprovals');
			$model->activateStaff();
			$this->out('End Processing Update Staff Status');
		} catch (\Exception $e) {
			$this->out('Update Staff Status > Exception : ');
			$this->out($e->getMessage());
		}
	}
}
