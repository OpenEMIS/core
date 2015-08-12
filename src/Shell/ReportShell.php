<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Datasource\Exception\RecordNotFoundException;
use Report\Model\Table\ReportProgressTable as Process;

class ReportShell extends Shell {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Report.ReportProgress');
	}

 	public function main() {
		$id = $this->args[0];

		try {
			$entity = $this->ReportProgress->get($id);

			if ($entity->status == 1) {
				$params = json_decode($entity->params, true);
				$format = $params['format'];
				switch($format) {
					case 'xlsx':
						$this->doExcel($entity);
						break;
				}
			} else {
				// not new process
			}
		} catch (RecordNotFoundException $ex) {
			echo 'Record not found (' . $id . ')';
		}
	}

	public function doExcel($entity) {
		try {
			$params = json_decode($entity->params, true);
			$feature = $params['feature'];
			$table = TableRegistry::get($feature);
			$name = $entity->name;

			echo date('d-m-Y H:i:s') . ': Start Processing ' . $name . "\n";
			$table->generateXLXS(['download' => false, 'process' => $entity]);
			echo date('d-m-Y H:i:s') . ': End Processing ' . $name . "\n";

		} catch (Exception $e) {
			$error = $e->getMessage();
			pr($error);
			$this->ReportProgress->updateAll(
				['status' => PROCESS::ERROR, 'error_message' => $error],
				['id' => $entity->id]
			);
		}
	}
}
