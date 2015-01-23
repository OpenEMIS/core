<?php
App::import('Vendor', 'XLSXWriter', array('file' => 'XLSXWriter/xlsxwriter.class.php'));

class ReportShell extends AppShell {
    public $uses = array('ReportProgress');
    
    public function main() {}
	
    public function _welcome() {}

    public function run() {
		$id = $this->args[0];

		$ReportProgress = $this->ReportProgress;
		
		$obj = $ReportProgress->findByIdAndStatus($id, 1);
		if ($obj) {
			$obj = $obj['ReportProgress'];
			$params = json_decode($obj['params'], true);
			pr($obj);
			$options = array_key_exists('options', $params) ? $params['options'] : array();
			$name = $obj['name'];
			$model = ClassRegistry::init($params['model']);

			echo 'Start Processing ' . $name . "\n";
			$ReportProgress->id = $id;

			$format = 'xlsx';
			$settings = array(
				'download' => false,
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
				'onComplete' => function($path) use ($id, $obj, $ReportProgress) {
					$expiryDate = new DateTime($obj['created']);
					$expiryDate->add(new DateInterval('P3D')); // config item
					$updateObj = array(
						'id' => $id,
						'file_path' => $path,
						'status' => 0,
						'expiry_date' => $expiryDate->format('Y-m-d H:i:s')
					);
					$ReportProgress->save($updateObj);
				}
			);

			$model->excel($format, $settings);

			/*
			$count = $this->DataRecord->find('count', $options);
			$percentCount = intval($count / 100);
			$this->ReportProgress->id = $id;
			$this->ReportProgress->saveField('total_records', $count);

			$rowCount = 1;
			$userId = $obj['created_user_id'];
			$userDir = WWW_ROOT . 'dataReports' . DS . $userId;
			if (!file_exists($userDir)) {
				mkdir($userDir);
			}
			$path = $userDir . DS . $filename . '.xlsx';
			$module = $obj['module'];
			$limit = 500;
			$writer = new XLSXWriter();
			$sheet = 'Sheet1';
			$writer->writeSheetRow($sheet, $header);

			// Populate data start
			
			$pages = ceil($count / $limit);
			$options['limit'] = $limit;
			
			for ($i=0; $i<$pages; $i++) {
				$options['offset'] = $i * $limit;
				$records = $this->DataRecord->find('all', $options);
				foreach ($records as $rec) {
					
					$rowCount++;

					$writer->writeSheetRow($sheet, $row);

					if ($rowCount % $percentCount == 0) {
						$this->ReportProgress->saveField('current_records', $rowCount);
					}
				}
			}
			$writer->writeToFile($path);
			echo "End Processing...\n";
			$expiryDate = new DateTime($obj['created']);
			$expiryDate->add(new DateInterval('P3D')); // config item
			$updateObj = array(
				'id' => $id,
				'file_path' => $path,
				'status' => 0,
				'current_records' => $count,
				'expiry_date' => $expiryDate->format('Y-m-d H:i:s')
			);
			$this->ReportProgress->save($updateObj);
			*/
		}
    }
}
