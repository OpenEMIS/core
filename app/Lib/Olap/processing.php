<?php
# PATH
define('OLAP_PATH',dirname(realpath(__FILE__)) . "/");
define('LIB_PATH',dirname(OLAP_PATH)."/");
define('APP_PATH',dirname(LIB_PATH)."/");
define('WEBROOT_PATH',dirname(LIB_PATH)."/webroot/");

require APP_PATH.'Vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

//register_shutdown_function( "fatal_handler" );
# Register function for when script is shutdown.
register_shutdown_function('shutdown');

date_default_timezone_set('Asia/Singapore');
# ini_set('memory_limit','32M');

# TYPE
define('SOURCE', 'source');
define('DESTINATION', 'destination');

# DIALECTS
define('DIALECT_MYSQL', 'mysql');
define('DIALECT_DB2', 'db2');
define('DIALECT_ORACLE', 'oracle');

# SQL LIMIT
define('LIMIT', 2000);

# XML FILE
define('XMLDATASOURCE', OLAP_PATH.'datasources.xml');
define('XMLPROCESSES', OLAP_PATH.'processes.xml');

# Process Status
define('PENDING', 1);
define('PROCESSING', 2);
define('COMPLETED', 3);
define('ABORT', 4);

try{
    # format log output.
    $output = "[%datetime%] %level_name%: %message%\n";
    $formatter = new LineFormatter($output);

	$processes = array();
	$datasources = NULL;
	$olap = NULL;

	# get start time and initial memory usage.
	$start_time = microtime(true);
	$initial_memory = memory_get_usage();

	# load options
	$shortopts = "";
	$shortopts .= "i::";
	// $shortopts .= "l::";
	$shortopts .= "p:";

	$longopts = array(
		 "id:",
		// "log:",
		 "process:"
	);

	$options = getopt($shortopts);

    if(isset($options['i']))
	 	$batchProcessId = $options['i'];
	else{
         errorProcess('Batch Process Id require.');
	}

    $log = new Logger('olap');
    $logFilename = WEBROOT_PATH."logs/olap/{$batchProcessId}.log";
    $logStream = new StreamHandler(WEBROOT_PATH."logs/olap/{$batchProcessId}.log", Logger::INFO);
    $logStream->setFormatter($formatter);
    $log->pushHandler($logStream);


    //$log->addInfo("========== OLAP Processing : START ==========");

    if(isset($options['p']))
	 	$processes = explode(',', $options['p']);
	else{
        errorProcess('Process Names require.',$logFilename);
	}

	# check XMLDATASOURCE files exist.
	if(file_exists(XMLDATASOURCE)){
		$datasources = simplexml_load_file(XMLDATASOURCE);
	}else{
        errorProcess(XMLDATASOURCE." doesn't exist.",$logFilename);
	}

	# check XMLPROCESSES files exist.
	if(	file_exists(XMLPROCESSES)){
		$olap = simplexml_load_file(XMLPROCESSES);
	}else{
        errorProcess(XMLPROCESSES." doesn't exist.",$logFilename);
	}

	# Connection DataSource for source and destination.
	$sourceDS = createDataConnection($datasources, SOURCE);
    $destinationDS = createDataConnection($datasources, DESTINATION);



	# Enable excaption modes
	$sourceDS->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$destinationDS->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );




    if(isAbort($sourceDS, $batchProcessId)){
        abortProcess();
    }else{
        startProcess($sourceDS, $batchProcessId);
    }

	if(sizeof($processes)>0){
		foreach ($processes as $value) {
			$process = array_pop($olap->xpath('//process[@name="'.trim($value).'"]'));
			processOlap($process,$batchProcessId);
		}
	}else{
		foreach ($olap->process as $current) {
			processOlap($current,$batchProcessId);
		}

    }
    completeProcess($sourceDS, $batchProcessId, $logFilename);
}
catch (PDOException $e) {
	errorProcess($e->getMessage() . " at Line {$e->getLine()}", $logFilename);
}catch (Exception $e){
    errorProcess($e->getMessage() . " at Line {$e->getLine()}");
}

/**
 * Process of clone/copying/transforming data from source to destination.
 * @param  SimpleXMLElement $process [description]
 * @return null
 */
function processOlap(SimpleXMLElement $process, $processId){
	global $sourceDS, $destinationDS, $log, $logFilename;

    $log->addInfo("---------------------------------------------");
    $log->addInfo("[Processing] '{$process['name']}' ");
    if(tableExist($sourceDS, $process['name'])){
        if($process->children()->count() > 0){
            $isTableDropped = false;


            if(isset($process->drop) AND tableExist($destinationDS, $process->drop['table'])){
                # Drop Table
                $log->addInfo("[Dropping] table: {$process->drop['table']}... ");
                $isTableDropped = dropTable($destinationDS, $process->drop);
                $log->addInfo($isTableDropped? "successful.":"failed.");
            }

            # check if table exist
            if(!tableExist($destinationDS, $process->create['table'])){
                # Create Table when table don't exist.
                $log->addInfo("[Creating] table: {$process->create['table']}... ");
                $log->addInfo(createTable($destinationDS, $process->create)? "successful.":"failed.");
            }

            if(!$isTableDropped){
                # Truncate Table
                $log->addInfo("[Truncating] table: {$process->truncate['table']}... ");
                $log->addInfo(truncateTable($destinationDS, $process->truncate)? "successful.":"failed.");
            }

            $counter = 0;
            $totalSourceRows = getCount($sourceDS, $process->select['table']);
            $log->addInfo("[Cloning] data from source to destination :");
            $log->addInfo(str_pad(($totalSourceRows > 0)? ceil(($counter/$totalSourceRows)*100):'100', 3, ' ', STR_PAD_LEFT) . "%");

            for($i=0; $i<ceil($totalSourceRows/LIMIT); $i++){

                if(isAbort($sourceDS, $processId)) abortProcess();

                $resultSet = getDataFromSource($sourceDS, $process->select, $i*LIMIT);
    //			$resultSetValue = array();
                // foreach ($resultSet as $data) {
                    // insertDataIntoDestination($destinationDS, $process->insert, $data);
                    // var_dump(array_values($data));
                    // $resultSetValue[] = implode(',', array_values($data));
                    // $counter++;
                // }
                insertDataIntoDestination($destinationDS, $process->insert, $resultSet);
                $log->addInfo(str_pad(ceil((($i+1)/ceil($totalSourceRows/LIMIT))*100), 3, ' ', STR_PAD_LEFT) . "%");
            }
    //		echo "\n\n";
    //        completeProcess($sourceDS, $processId, $logFilename);

        }
    }else{
        $log->addInfo("[Warning] skip processing of '{$process['name']}', does not exist in source.");
    }

}

/**
 * Check if the table exist.
 * @param  PDO    $datasource [description]
 * @param  string $table      [description]
 * @return boolean             [description]
 */
function tableExist(PDO $datasource, $table=null) {
	// echo "===========\n";
	// echo $table."\n";
	$statement = "SHOW TABLES LIKE '{$table}'";
	$query = $datasource->query($statement);
	$query->setFetchMode(PDO::FETCH_ASSOC);
	// var_dump($query->execute());
	// var_dump($resultSet->fetch());
	return ($query->fetch())? TRUE:FALSE;

}

/**
 * Create DB table.
 * @param  PDO              $destination   [description]
 * @param  SimpleXMLElement $createElement [description]
 * @return boolean                         [description]
 */
function createTable(PDO $destination, SimpleXMLElement $createElement) {
	$statement = getQueryStatement($createElement->SQL);
	// echo trim($statement)."\n";
	$query = $destination->prepare($statement);

	return $query->execute();
}

/**
 * Insert data into table to destination DB
 * @param  PDO              $destination  [description]
 * @param  SimpleXMLElement $inputElement [description]
 * @param  Array            $data         [description]
 * @return boolean                        [description]
 */
function insertDataIntoDestination(PDO $destination, SimpleXMLElement $inputElement, Array $resultSet) {
    global $log;
	$origStatement = trim(getQueryStatement($inputElement->SQL));

	$destination->beginTransaction();
	$query = $destination->prepare($origStatement);
    foreach ($resultSet as $data) {
		$query->execute($data);
	}
	return $destination->commit();
}

function placeholders($text, $count=0, $separator=","){
    $result = array();
    if($count > 0){
        for($x=0; $x<$count; $x++){
            $result[] = $text;
        }
    }

    return implode($separator, $result);
}

/**
 * Get data from Source DB
 * @param  PDO              $source        [description]
 * @param  SimpleXMLElement $selectElement [description]
 * @param  integer          $offset        [description]
 * @return [type]                          [description]
 */
function getDataFromSource(PDO $source, SimpleXMLElement $selectElement, $offset=0){
    global $log;
	$resultSet = array();
	$statement = trim(getQueryStatement($selectElement->SQL));
	// remove the ';' and the end of the the statement
	if(strpos($statement, ';', sizeof($statement))) $statement = substr($statement, 0, strpos($statement, ';', sizeof($statement)));

	$statement .= " LIMIT {$offset},".LIMIT;
//    $log->addInfo($statement);
	// echo "QUERY: ".trim($statement)."\n";
	$query = $source->query($statement);
	$query->setFetchMode(PDO::FETCH_ASSOC);
	while($row = $query->fetch()){
		array_push($resultSet, $row);
	}

	return $resultSet;
}

/**
 * Truncate table of given table
 * @param  PDO    $datasource      [description]
 * @param  [type] $truncateElement [description]
 * @return [type]                  [description]
 */
function truncateTable(PDO $datasource, $truncateElement) {
	$statement = getQueryStatement($truncateElement->SQL);
	// $statement = "TRUNCATE TABLE {$table}";
	$query = $datasource->prepare($statement);
	return $query->execute();
}

/**
 * Drop table of given table
 * @param  PDO    $datasource      [description]
 * @param  [type] $dropElement     [description]
 * @return [type]                  [description]
 */
function dropTable(PDO $datasource, $dropElement) {
	$statement = getQueryStatement($dropElement->SQL);
	// $statement = "TRUNCATE TABLE {$table}";
	$query = $datasource->prepare($statement);
	return $query->execute();
}

/**
 * Get the query statement based on the SimpleXMLElement provided
 *
 * @param  SimpleXMLElement $sql SimpleXMLElement class
 * @return String                Return the query statment from the element.
 */
function getQueryStatement(SimpleXMLElement $sql){
	$query = '';
	foreach ($sql as $provder) {
		if($provder['dialect'] == DIALECT_MYSQL){
			$query = (string) $provder;
			break;
		}elseif($provder['dialect'] == 'generic'){
			$query = (string) $provder;
		}
	}
	return $query;
}

/**
 * Create database connection base on the datasource given
 *
 * @param  SimpleXMLElement $datasources Provide a list of datasources avaliable to connection.
 * @param  String           $type        The type of connection.
 * @return PDO                           Return a PDO object.
 */
function createDataConnection(SimpleXMLElement $datasources, $type = SOURCE) {
	// $datasources = simplexml_load_file('datasources.xml');
	$selectedDataSource = null;

	foreach($datasources as $datasource){
		if($datasource['type'] == $type){
			$selectedDataSource = $datasource;
			break;
		}
	}

	// var_dump(empty($selectedDataSource));
	$hostConnect = "";
	$hostConnect .= $datasource['provider'].":";
	$hostConnect .= "host=".$datasource->connection['host'];
	if (!empty($datasource->connection['port'])) $hostConnect .= ";port=".$datasource->connection['port'];
	$hostConnect .= ";dbname=".$datasource->connection['database'];
	// echo "{$hostConnect}\n";

	return new PDO($hostConnect, $datasource->connection['username'], $datasource->connection['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$datasource->connection['encoding']}") );

}

/**
 * Get the row count in a given table
 *
 * @param  PDO    $datasource Datasource of the table.
 * @param  String $table      The table that count will applied to.
 * @return int                Row count.
 */
function getCount(PDO $datasource = null, $table = null) {
	$totalRows = 0;

	if(is_null($datasource) OR is_null($table)) return $totalRows;

	$query = $datasource->query("SELECT COUNT(*) FROM {$table}");
	// $query = $sourceDS->query("SELECT * FROM census_students LIMIT 10");
	$query->setFetchMode(PDO::FETCH_ASSOC);
	while($row = $query->fetch()){
		$totalRows = intval(array_shift($row));
	}
	return $totalRows;
}

function startProcess(PDO $ds, $processId){
    $stmt = $ds->prepare("UPDATE `batch_processes` SET `batch_processes`.`status` = ".PROCESSING." WHERE id = {$processId}");
    $stmt->execute();
    return $stmt->rowCount()>0 ? true:false;
}

function completeProcess(PDO $ds, $processId, $logUrl){
    $stmt = $ds->prepare("UPDATE `batch_processes` SET `batch_processes`.`status` = '".COMPLETED."', `batch_processes`.`file_name` = '{$logUrl}',`batch_processes`.`finish_date` = '". date('Y-m-d h:i:s', time()) ."' WHERE id = {$processId}");
    $stmt->execute();
    return $stmt->rowCount()>0 ? true:false;
}

function abortProcess(){
    global $log;
    $log->addInfo('ABORT PROCESSING.');
    echoLog('ABORT PROCESSING.');
    die();
}

function errorProcess($msg,$logUrl=null){
    global $log, $batchProcessId, $sourceDS;
    if(!is_null($logUrl)){
        $stmt = $sourceDS->prepare("UPDATE `batch_processes` SET `batch_processes`.`status` = '".ABORT."', `batch_processes`.`file_name` = '{$logUrl}',`batch_processes`.`finish_date` = '". date('Y-m-d h:i:s', time()) ."' WHERE id = {$batchProcessId}");
        $stmt->execute();
    }
    $log->addError($msg);
    echoLog($msg);
    exit();
}

function isAbort(PDO $ds, $processId){
    $stmt = $ds->prepare("SELECT `status` FROM `batch_processes` WHERE id = {$processId} LIMIT 1");
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);

    $row  = $stmt -> fetch();
    return ($row['status'] == ABORT)? true:false;
}

function echoLog($msg, $type="Info"){
    echo date('Y-m-d H:i:s')." Info: {$msg}".PHP_EOL;
}

/**
 * Display status of the execution of the current script.
 * @return null
 */
function shutdown() {
	global $start_time, $initial_memory, $log;
    $log->addInfo("---------------------------------------------");
    $log->addInfo("Execution took: ". number_format(microtime(true) - $start_time, 3)." Seconds.");
    $log->addInfo("Initial Memory Usage: ". $initial_memory ." bytes.");
    $log->addInfo("Final Memory Usage: ". memory_get_usage() ." bytes.");
    $log->addInfo("Peak Memory Usage: ". memory_get_peak_usage() ." bytes.");
    $log->addInfo("========== OLAP Processing : END ============");

    echoLog("---------------------------------------------");
    echoLog("Execution took: ". number_format(microtime(true) - $start_time, 3)." Seconds.");
    echoLog("Initial Memory Usage: ". $initial_memory ." bytes.");
    echoLog("Final Memory Usage: ". memory_get_usage() ." bytes.");
    echoLog("Peak Memory Usage: ". memory_get_peak_usage() ." bytes.");
    echoLog("========== OLAP Processing : END ============");

}

function fatal_handler() {
    global $log;
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

    if( $error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
    }
//    $trace = print_r( debug_backtrace( false ), true );
    $errorString = "{$errstr}".PHP_EOL;
    $errorString .= "Errno: {$errno}".PHP_EOL;
    $errorString .= "File: {$errfile}".PHP_EOL;
    $errorString .= "Line: {$errline}";
//    $errorString .= "Trace: {$trace}";

    $log->addError($errorString);
    echoLog($errorString, 'Error');
}
?>
