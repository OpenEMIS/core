<?php
App::import('Vendor', 'XLSXWriter', array('file' => 'XLSXWriter/xlsxwriter.class.php'));

class ReportShell extends AppShell {
    public $uses = array('ReportProgress');
    
    public function main() {}
	
    public function _welcome() {}

    public function run() {
    	$id = $this->args[0];
		
		$obj = $this->ReportProgress->findByIdAndStatus($id, 1);

		if ($obj) {
			$obj = $obj['ReportProgress'];
			$params = json_decode($obj['params'], true);
			$format = $params['format'];
			switch($format) {
				case 'excel':
					$this->doExcel($obj, $id);
					break;
			}
		}
    }

    public function doExcel($obj, $id) {
    	$ReportProgress = $this->ReportProgress;
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
