<?php
namespace App\Shell;

use ArrayObject;
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
		
		ini_set('memory_limit', '-1'); //  -1 is for infinite , By default it is 128M & it's not sufficient

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
					case 'csv':
						$this->doCsv($entity);
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
			$name = $entity->name;

			if ($entity->module == 'CustomReports') {
				$excelParams = new ArrayObject([]);
				$excelParams['className'] = 'Report.CustomReports';
				$excelParams['requestQuery'] = $params;
				$excelParams['process'] = $entity;

				$table = TableRegistry::get($excelParams['className']);
				echo date('d-m-Y H:i:s') . ': Start Processing ' . $name . "\n";
				$table->renderExcelTemplate($excelParams);
				echo date('d-m-Y H:i:s') . ': End Processing ' . $name . "\n";

			} else {
				$table = TableRegistry::get($feature);
				echo date('d-m-Y H:i:s') . ': Start Processing ' . $name . "\n";
				$table->generateXLXS(['download' => false, 'process' => $entity]);
				echo date('d-m-Y H:i:s') . ': End Processing ' . $name . "\n";
			}

		} catch (Exception $e) {
			$error = $e->getMessage();
			pr($error);
			$this->ReportProgress->updateAll(
				['status' => PROCESS::ERROR, 'error_message' => $error],
				['id' => $entity->id]
			);
		}
	}

	public function doCsv($entity) {
		try {
			$params = json_decode($entity->params, true);
			$feature = $params['feature'];
			$name = $entity->name;

			if ($entity->module == 'CustomReports') {
				$table = TableRegistry::get('Report.CustomReports');
				echo date('d-m-Y H:i:s') . ': Start Processing ' . $name . "\n";
				$table->generateCSV(['process' => $entity, 'requestQuery' => $params]);
				echo date('d-m-Y H:i:s') . ': End Processing ' . $name . "\n";
			}
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
