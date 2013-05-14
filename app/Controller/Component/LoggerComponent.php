<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

class LoggerComponent extends Component {
	private $controller;
	public $logName;
	public $logPath;
	public $logFile;
	public $defaultLog;
	
	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) { }
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) { }
	
	//called after Controller::render()
	public function shutdown(Controller $controller) { }
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) { }
	
	public function init($logName) {
		$this->logName = $logName;
		$this->logFile = $logName . '%s.log';
		$this->defaultLog = $logName . '.log';
		
		$this->logPath = WWW_ROOT . 'logs' . DS;
		if(!file_exists($this->logPath)) {
			mkdir($this->logPath);
		}
		
		$this->logPath = $this->logPath . $logName . DS;
		if(!file_exists($this->logPath)) {
			mkdir($this->logPath);
		}
	}
	
	public function start() {
		$this->flush(LOGS . $this->defaultLog);
	}
	
	public function end() {
		$pathToLog = "";
		
		if(file_exists(LOGS . $this->defaultLog)) {
			$timestamp = filemtime(LOGS . $this->defaultLog);
			$date = date('Y_m_d', $timestamp);
			
			$logPath = $this->logPath . $date . DS;
			if(!file_exists($logPath)) {
				mkdir($logPath);
			}
			
			$log = sprintf($this->logFile, date('_Ymd_His'));
			$pathToLog = $logPath . $log;
			if(!copy(LOGS . $this->defaultLog, $pathToLog)) {
				$this->log('Unable to copy ' . $this->logName . ' logs to ' . $logPath, 'error');
				copy(LOGS . $this->defaultLog, LOGS . $log);
			}
		} else {
			$this->log(ucfirst($this->logName) . ' log does not exists', 'error');
		}
		return $pathToLog;
	}
	
	public function write($msg) {
		$this->log($msg, $this->logName);
	}
	
	public function flush($logFile) {
		if(!file_exists($logFile)) {
			$this->log(" ", $this->logName);
		}
		$fp = fopen($logFile, "r+");
		ftruncate($fp, 0);
		fclose($fp);
	}
	
	public function getLogs() {
		$entries = array();
		$list = scandir($this->logPath, 1);
		foreach($list as $file) {
			if($file != "." && $file != "..") {
				$entries[] = $file;
			}
		}
		return $entries;
	}
}
?>
