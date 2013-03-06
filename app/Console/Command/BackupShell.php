<?php

App::uses('CakeSchema', 'Model');
App::uses('ConnectionManager', 'Model');
App::uses('Inflector', 'Utility');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
// App::uses('Sanitize', 'Utility');

class BackupShell extends Shell {
	public $tasks = array('Common');

/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 * @access public
 */
	var $args;
	public $path;
	public $limit = 100;
	public $exlucedTablePatterns = array('security_','config_items','batch_');
	private $dataSourceName = 'default';
	public $batchProcessId = '';

	/**
 * Override main() for help message hook
 *
 * @access public
 */
	function main(){
		
	}
	function run() {
		if($this->isBackupRunning()) exit; // security so  
		if(count($this->args) > 0){
			$this->insertToBatchProcess($this->args[0]); //pass the userId who started the job
			try{
				//$path = APP_DIR . DS .'Backups' . DS;
				$path = ROOT.DS.'app'. DS .'Backups' . DS;
				$Folder = new Folder($path, true);

				$fileSufix = date('Ymd\_His') . '.sql';
				$file = $path . $fileSufix;
				if (!is_writable($path)) {
					trigger_error('The path "' . $path . '" isn\'t writable!', E_USER_ERROR);
				}

				$this->out("Backuping...\n");
				$File = new File($file);

				$db = ConnectionManager::getDataSource($this->dataSourceName);

				$config = $db->config;
				$this->connection = "default";

				foreach ($db->listSources() as $table) {
					// if (preg_match("/^security_/", $table) || preg_match("/^config_items$/", $table) || preg_match("/^batch_indicator_results$/", $table)) { continue; }

					//if (preg_match("/^security_/", $table) || preg_match("/^config_items$/", $table)) { continue; }

					if(strpos($table,'security_')  !== false || strpos($table,'config_items')  !== false || strpos($table,'batch_')  !== false) continue;


					$table = str_replace($config['prefix'], '', $table);

					// $table = str_replace($config['prefix'], '', 'dinings');
					$ModelName = Inflector::classify($table);

					$Model = ClassRegistry::init($ModelName);
					try{
						$DataSource = $Model->getDataSource();
					}catch(Exception $e){
						continue;
					}

					$this->Schema = new CakeSchema(array('connection' => $this->connection));

					$cakeSchema = $db->describe($table);

					// $CakeSchema = new CakeSchema();

					// $this->Schema->tables = array($table => $cakeSchema);

					// $File->write("\n/* Drop statement for {$table} */\n");
					// $File->write("SET foreign_key_checks = 0;");
					// // $File->write($DataSource->dropSchema($this->Schema, $table) . "\n");
					// $File->write($DataSource->dropSchema($this->Schema, $table));
					// $File->write("SET foreign_key_checks = 1;\n");

					// $File->write("\n/* Backuping table schema {$table} */\n");
					// $File->write($DataSource->createSchema($this->Schema, $table) . "\n");

					$File->write("\n--/* Truncate table data {$table} */\n");
					$File->write("\nTRUNCATE TABLE {$table} \n");

					$File->write("\n--/* Backuping table data {$table} */\n");


					unset($valueInsert, $fieldInsert);
					//count HERE

					$countM = $Model->find('count');
					$recusive = ceil($countM / $this->limit);
					$quantity = 0;
					for($a=0;$a<$recusive;$a++){
						$fieldInsert = array();
						$offset = ($this->limit*$a);
						// $rows = $Model->find('all', array('recursive' => -1,'limit'=>10,'offset'=>$offset));
						$rows = $Model->find('all', array('recursive' => -1,'limit'=>$this->limit,'offset'=>$offset));

						if (sizeOf($rows) > 0) {

							$fields = array_keys($rows[0][$ModelName]);
							$values = array_values($rows);
							$count = count($fields);

							for ($i = 0; $i < $count; $i++) {
								//unset($fieldInsert);
								$fieldInsert[] = $DataSource->name($fields[$i]);
							}
							$fieldsInsertComma = implode(', ', $fieldInsert);

							foreach ($rows as $k => $row) {
								unset($valueInsert);
								for ($i = 0; $i < $count; $i++) {
									$valueInsert[] = $DataSource->value(utf8_encode($row[$ModelName][$fields[$i]]), $Model->getColumnType($fields[$i]), false);
								}

								$query = array(
									'table' => $DataSource->fullTableName($table),
									'fields' => $fieldsInsertComma,
									'values' => implode(', ', $valueInsert)
								);
								$DataSource->renderStatement('create', $query);
								$File->write($DataSource->renderStatement('create', $query) . ";\n");
								$quantity++;
							}

						}
					}

					$this->out('Model "' . $ModelName . '" (' . $countM . ')');
				}
				$File->close();
				$this->out("\nFile \"" . $file . "\" saved (" . filesize($file) . " bytes)\n");

				if (class_exists('ZipArchive') && filesize($file) > 100) {
					$this->out('Zipping...');
					$zip = new ZipArchive();
					$zip->open($file . '.zip', ZIPARCHIVE::CREATE);
					$zip->addFile($file, $fileSufix);
					$zip->close();
					$this->out("Zip \"" . $file . ".zip\" Saved (" . filesize($file . '.zip') . " bytes)\n");
					$this->out("Zipping Done!");
					if (file_exists($file . '.zip') && filesize($file) > 10) {
						unlink($file);
					}
					$this->out("Database Backup Successful.\n");
				}
				$this->Common->updateStatus($this->batchProcess['BatchProcess']['id'],3);
			}catch(Exception $e){
				pr($e);
				$this->Common->updateStatus($this->batchProcess['BatchProcess']['id'],'-1');
			}
		}
	}
	
	private function insertToBatchProcess($usedId = 1){
		$BatchProcess = ClassRegistry::init('BatchProcess');
		$tmp = array();
		$BatchProcess->create();
		$tmp = array(
			'name'=>'Backup Database',
			'file_name'=>  '',
			'start_date'=>date('Y-m-d h:i:s'),
			'finish_date'=>'0000-00-00 00:00:00',
			'reference_id' => '',
			'reference_table' => '',
			'created_user_id' => $usedId,
			'status'=>2
			);
		$this->batchProcess = $BatchProcess->save($tmp);
	}
	
	
	private function isBackupRunning(){
		$BatchProcess = ClassRegistry::init('BatchProcess');
		$data = $BatchProcess->find('first',array('conditions'=>array('name'=>'Backup Database','NOT' =>array('status' => array('3','-1')))));
		return (count($data)>0 && $data ? TRUE:FALSE);
	}
	
	
	function restore(){
		if($this->isBackupRunning()) exit; // security so  
		if(empty($this->args[0])) { echo "no Input detected";  exit;}
		$path = ROOT.DS.'app'. DS .'Backups' . DS;
		echo $tmpath = ROOT.DS.'app'. DS .'tmp' ;
		$backupFolder = new Folder($path);
		// Get the list of files
		list($dirs, $files)     = $backupFolder->read();
		// Remove any un related files
		foreach ($files as $i => $file) {
        if (!preg_match( '/\.sql/', $file))  {
                unset($files[$i]);
            }
        }
        // Sort, explode the files to an array and list files
        sort($files, SORT_NUMERIC);
        foreach ($files as $i => $file) {
            $fileParts = explode(".", $file);
            $backup_date = strtotime(str_replace("_", "", $fileParts[0]));
            $this->out("[".$i."]: ".date("F j, Y, g:i:s a", $backup_date));
        }
        App::import('Model', 'AppModel');
        $model = new AppModel(false, false);
        // Prompt for the file to restore to
        $this->hr();
        //$u_response = $this->in('Type Backup File Number? [or press enter to skip]');
		$u_response = $this->args[0];
        if ($u_response == "") {
	        $this->out('Exiting');
	    } else {
	    	$zipfile = $path.$files[$u_response];
	    	if(array_key_exists($u_response, $files)){
	    		$this->out('Restoring file: '.$zipfile);
	    		$fileParts = explode(".",$files[$u_response]);

	    		if(isset($fileParts[2]) && $fileParts[2]=='zip'){
	    			$this->out('Unzipping File');
	    			if (class_exists('ZipArchive')) {
	    				$zip = new ZipArchive;
	    				if($zip->open($zipfile) === TRUE){
	    					$zip->extractTo($tmpath);
	    					$unzipped_file = $tmpath.DS.$zip->getNameIndex(0);

	    					echo $unzipped_file;
	    					
	    					$zip->close();
	    					$this->out('Successfully Unzipped');
	    				} else {
	    					$this->out('Unzip Failed');
	    					$this->_stop();
	    				}
	    			} else {
	    				$this->out('ZipArchive not found, cannot Unzip File!');
	    				$this->_stop();
	    			}
	    		}

	    		$file = fopen($unzipped_file,"r");
				$meta = stream_get_meta_data($file);
				if($meta['mode']) {
					while(!feof($file)) {
						$sql = fgets($file);
						if ($sql == "\n" || preg_match('/^--/', $sql)) { continue; }
						$resultSrc = $model->query($sql);
					}
					fclose($file);
					unlink($unzipped_file);
				} else {
					$this->out("Couldn't load contents of file {$unzipped_file}, aborting...");
	    			unlink($unzipped_file);
            		$this->_stop();
				}
	    	} else {
	    		$this->out("Invalid File Number");
	    		$this->_stop();
	    	}
		}

	}
}
?>