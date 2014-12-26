<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

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

class AlertComponent extends Component {
	private $controller;
	
	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) {}
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) {}
	
	//called after Controller::render()
	public function shutdown(Controller $controller) {}
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {}
	
	public function trigger($args){
		$params = array('Alert', 'run');
		
		foreach($args as $arg){
			$params[] = $arg;
		}
		
		$cmd = sprintf("%sConsole/cake.php -app %s %s", APP, APP, implode(' ', $params));
		$nohup = 'nohup %s > %stmp/logs/processes.log & echo $!';
		$shellCmd = sprintf($nohup, $cmd, APP);
		$this->log($shellCmd, 'debug');
		//pr($shellCmd);
		$output = array();
		exec($shellCmd, $output);
	}
	
}
?>
