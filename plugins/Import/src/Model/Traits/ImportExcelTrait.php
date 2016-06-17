<?php
namespace Import\Model\Traits;

use DateTime;
use DateInterval;
use Cake\Event\Event;
use Cake\Utility\Inflector;

trait ImportExcelTrait
{
	protected $RECORD_HEADER = 2;
	protected $FIRST_RECORD = 3;
	protected $MAX_ROWS = 2000;
	protected $MAX_SIZE = 524288;
	protected $rootFolder = 'import';
	protected $fileTypesMap = [
		// 'csv' 	=> 'text/plain',
		// 'csv' 	=> 'text/csv',
		'xls' 	=> ['application/vnd.ms-excel', 'application/vnd.ms-office'],
		'xlsx' 	=> ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
		// Use for openoffice .xls format
		'ods' 	=> ['application/vnd.oasis.opendocument.spreadsheet'],
		'zip' 	=> ['application/zip']
	];

	protected function prepareDownload() {
		$folder = WWW_ROOT . $this->rootFolder;
		if (!file_exists($folder)) {
			umask(0);
			mkdir($folder, 0777);
		} else {
			$fileList = array_diff(scandir($folder), array('..', '.'));
			$now = new DateTime();
			// delete all old files that are more than one hour old
			$now->sub(new DateInterval('PT1H'));

			foreach ($fileList as $file) {
				$path = $folder . DS . $file;
				$timestamp = filectime($path);
				$date = new DateTime();
				$date->setTimestamp($timestamp);

				if ($now > $date) {
					if (!unlink($path)) {
						$this->_table->log('Unable to delete ' . $path, 'export');
					}
				}
			}
		}
		
		return $folder;
	}
	
	protected function performDownload($excelFile) {
		$folder = WWW_ROOT . $this->rootFolder;
		$excelPath = $folder . DS . $excelFile;
		$filename = basename($excelPath);
		
		header("Pragma: public", true);
		header("Expires: 0"); // set expiration time
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment; filename=".$filename);
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".filesize($excelPath));
		echo file_get_contents($excelPath);
	}

	protected function checkRowCells($sheet, $totalColumns, $row) {
		$cellsState = [];
		for ($col=0; $col < $totalColumns; $col++) {
			$cell = $sheet->getCellByColumnAndRow($col, $row);
			$value = $cell->getValue();
			if (empty($value)) {
				$cellsState[] = false;
			} else {
				$cellsState[] = true;
			}
		}
		return in_array(true, $cellsState);
	}
	
	protected function beginExcelHeaderStyling( $objPHPExcel, $dataSheetName, $lastRowToAlign = 2, $title = '', $titleColumn = 'C' ) {
		if (empty($title)) {
			$title = $dataSheetName;
		}
		$activeSheet = $objPHPExcel->getActiveSheet();
		$activeSheet->setTitle( $dataSheetName );

		$gdImage = imagecreatefromjpeg(ROOT . DS . 'plugins' . DS . 'Import' . DS . 'webroot' . DS . 'img' . DS . 'openemis_logo.jpg');
		$objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
		$objDrawing->setName('OpenEMIS Logo');
		$objDrawing->setDescription('OpenEMIS Logo');
		$objDrawing->setImageResource($gdImage);
		$objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
		$objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
		$objDrawing->setHeight(100);
		$objDrawing->setCoordinates('A1');
		$objDrawing->setWorksheet($activeSheet);

		$activeSheet->getRowDimension(1)->setRowHeight(75);
		$activeSheet->getRowDimension(2)->setRowHeight(25);

		$headerLastAlpha = $this->getExcelColumnAlpha('last');
		$activeSheet->getStyle( "A1:" . $headerLastAlpha . "1" )->getFont()->setBold(true)->setSize(16);
		$activeSheet->setCellValue( $titleColumn."1", $title );
		$style = [
	        'alignment' => [
	            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
	            'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
	        ]
	    ];
	    $activeSheet->getStyle("A1:". $headerLastAlpha . $lastRowToAlign)->applyFromArray($style);
	}

	protected function endExcelHeaderStyling( $objPHPExcel, $headerLastAlpha, $applyFillFontSetting = [], $applyCellBorder = [], $titleColumn = 'C' ) {
		if (empty($applyFillFontSetting)) {
			$applyFillFontSetting = ['s'=>2, 'e'=>2];
		}
		if (empty($applyCellBorder)) {
			$applyCellBorder = ['s'=>2, 'e'=>2];
		}

		$activeSheet = $objPHPExcel->getActiveSheet();

		// merging should start from cell C1 instead of A1 since the title is already set in cell C1 in beginExcelHeaderStyling()
		if (!in_array($headerLastAlpha, ['A','B','C'])) {
			$activeSheet->mergeCells($titleColumn.'1:'. $headerLastAlpha .'1');
		}
		$activeSheet->getStyle("A". $applyFillFontSetting['s'] .":". $headerLastAlpha . $applyFillFontSetting['e'])->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $activeSheet->getStyle("A". $applyFillFontSetting['s'] .":". $headerLastAlpha . $applyFillFontSetting['e'])->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('6699CC'); // OpenEMIS Core product color
		$activeSheet->getStyle("A". $applyCellBorder['s'] .":". $headerLastAlpha . $applyCellBorder['e'])->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
	}

	protected function setImportDataTemplate( $objPHPExcel, $dataSheetName, $header, $autoTitle = true,  $titleColumn = 'C') {

		$objPHPExcel->setActiveSheetIndex(0);

		if ($autoTitle) {
			$this->beginExcelHeaderStyling( $objPHPExcel, $dataSheetName, 2, __(Inflector::humanize(Inflector::tableize($this->_table->alias()))) .' '. $dataSheetName );
		} else {
			$this->beginExcelHeaderStyling( $objPHPExcel, $dataSheetName, 2, '', $titleColumn);
		}

		$activeSheet = $objPHPExcel->getActiveSheet();
		$currentRowHeight = $activeSheet->getRowDimension(2)->getRowHeight();
		foreach ($header as $key=>$value) {
			$alpha = $this->getExcelColumnAlpha($key);
			$activeSheet->setCellValue( $alpha . "2", $value);
			if (strlen($value)<50) {
				$activeSheet->getColumnDimension( $alpha )->setAutoSize(true);
			} else {
				$activeSheet->getColumnDimension( $alpha )->setWidth(35);
				$currentRowHeight = $this->suggestRowHeight( strlen($value), $currentRowHeight );
				$activeSheet->getRowDimension(2)->setRowHeight( $currentRowHeight );
				$activeSheet->getStyle( $alpha . "2" )->getAlignment()->setWrapText(true);
			}
		}
		$headerLastAlpha = $this->getExcelColumnAlpha(count($header)-1);

		$this->endExcelHeaderStyling( $objPHPExcel, $headerLastAlpha, [], [], $titleColumn);

	}

	/**
	 * Get the string representation of a column based on excel grid
	 * @param  mixed $column_number either an integer or a string named as "last"
	 * @return string               the string representation of a column based on excel grid
	 * @todo  the alpha string array values should be auto-generated instead of hard-coded
	 */
	protected function getExcelColumnAlpha($column_number) {
		$alpha = [
			'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
			'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
			'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ',
			'CA','CB','CC','CD','CE','CF','CG','CH','CI','CJ','CK','CL','CM','CN','CO','CP','CQ','CR','CS','CT','CU','CV','CW','CX','CY','CZ',
			'DA','DB','DC','DD','DE','DF','DG','DH','DI','DJ','DK','DL','DM','DN','DO','DP','DQ','DR','DS','DT','DU','DV','DW','DX','DY','DZ',
			'EA','EB','EC','ED','EE','EF','EG','EH','EI','EJ','EK','EL','EM','EN','EO','EP','EQ','ER','ES','ET','EU','EV','EW','EX','EY','EZ',
			'FA','FB','FC','FD','FE','FF','FG','FH','FI','FJ','FK','FL','FM','FN','FO','FP','FQ','FR','FS','FT','FU','FV','FW','FX','FY','FZ'
		];
		if ($column_number === 'last') {
			$column_number = count($alpha) - 1;
		}
		return $alpha[$column_number];		
	}

	/**
	 * Check if the uploaded file is the correct template by comparing the headers extracted from mapping table
	 * and first row of the uploaded file record
	 * @param  array  		$header      	The headers extracted from mapping table according to active model
	 * @param  WorkSheet 	$sheet      	The worksheet object
	 * @param  integer 		$totalColumns 	Total number of columns to be checked
	 * @param  integer 		$row          	Row number
	 * @return boolean               		the result to be return as true or false
	 */
	protected function isCorrectTemplate($header, $sheet, $totalColumns, $row) {
		$cellsValue = [];
		for ($col=0; $col < $totalColumns; $col++) {
			$cell = $sheet->getCellByColumnAndRow($col, $row);
			$cellsValue[] = $cell->getValue();
		}
		return $header === $cellsValue;
	}
	
/**
 * @link("PHP get actual maximum upload size", http://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size)
 */
	// Returns a file size limit in bytes based on the PHP upload_max_filesize
	// and post_max_size
	protected function file_upload_max_size() {
		static $max_size = -1;

		if ($max_size < 0) {
			// Start with post_max_size.
			$max_size = $this->post_upload_max_size();

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->upload_max_filesize();

			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}
		return $max_size;
	}

	protected function parse_size($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		} else {
			return round($size);
		}
	}
/**
 * 
 */

	protected function post_upload_max_size() {
		$max_size = $this->parse_size(ini_get('post_max_size'));
		$memory_limit = $this->system_memory_limit();
		if ($max_size == 0 && $memory_limit > 0) {
			$max_size = $memory_limit;
		}
		return $max_size;
	}

	protected function system_memory_limit() {
		return $this->parse_size(ini_get('memory_limit'));
	}

	protected function upload_max_filesize() {
		return $this->parse_size(ini_get('upload_max_filesize'));
	}

/**
 * http://codereview.stackexchange.com/questions/6476/quick-way-to-convert-bytes-to-a-more-readable-format
 * @param  [type] $bytes [description]
 * @return [type]        [description]
 */
	protected function bytesToReadableFormat($bytes) {
		$KILO = 1024;
		$MEGA = $KILO * 1024;
		$GIGA = $MEGA * 1024;
		$TERA = $GIGA * 1024;

		if ($bytes < $KILO) {
	        return $bytes . 'B';
	    }
	    if ($bytes < $MEGA) {
	        return round($bytes / $KILO, 2) . 'KB';
	    }
	    if ($bytes < $GIGA) {
	        return round($bytes / $MEGA, 2) . 'MB';
	    }
	    if ($bytes < $TERA) {
	        return round($bytes / $GIGA, 2) . 'GB';
	    }
	    return round($bytes / $TERA, 2) . 'TB';
	}

/**
 * @link("Upload errors defination", http://php.net/manual/en/features.file-upload.errors.php#115746)
 * For reference.
 */
	protected $phpFileUploadErrors = array(
	    0 => 'There is no error, the file uploaded with success',
	    1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
	    2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
	    3 => 'The uploaded file was only partially uploaded',
	    4 => 'No file was uploaded',
	    6 => 'Missing a temporary folder',
	    7 => 'Failed to write file to disk.',
	    8 => 'A PHP extension stopped the file upload.',
	);

}