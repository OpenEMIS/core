<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\Datasource\Exception\RecordNotFoundException;

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
		$ReportProgress->id = $id;

		try {
			$params = json_decode($obj['params'], true);
			pr($obj);
			
			$options = array_key_exists('options', $params) ? $params['options'] : array();
			$name = $obj['name'];
			$model = ClassRegistry::init($params['model']);

			echo date('d-m-Y H:i:s') . ': Start Processing ' . $name . "\n";

			$format = 'xlsx';
			$settings = array(
				'download' => false,
				'delete' => false,
				'onStartSheet' => function($count, $pages) use ($ReportProgress) {
					$ReportProgress->saveField('total_records', $count);
				},
				'onEndSheet' => function($count) use ($ReportProgress) {
					$ReportProgress->saveField('current_records', $count);
				},
				'onBeforeWrite' => function($rowCount, $percentCount) use ($ReportProgress) {
					if (($percentCount > 0 && $rowCount % $percentCount == 0) ||  $percentCount == 0)  {
						$ReportProgress->saveField('current_records', $rowCount);
					}
				},
				'onComplete' => function($path) use ($ReportProgress) {
					$ReportProgress->saveField('status', 0);
					$ReportProgress->saveField('file_path', $path);
				},
				'options' => $options
			);

			$model->excel($format, $settings);
			echo date('d-m-Y H:i:s') . ': End Processing ' . $name . "\n";
		} catch (Exception $e) {
			$error = $e->getMessage();
			pr($error);
			$ReportProgress->saveField('status', -1);
			$ReportProgress->saveField('error_message', $error);
		}
	}
}
