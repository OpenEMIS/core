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

App::uses('IndicatorComponent', 'DataProcessing.Controller/Component');

class IndicatorShell extends AppShell {
    public $uses = array(
		'DataProcessing.BatchProcess',
		'DataProcessing.BatchIndicator'
	);
    
    public function main() {}
	
    public function _welcome() {}
	
    public function run() {
		if(sizeof($this->args) == 2) {
			$processId = $this->args[0];
			$format = $this->args[1];
			
			try {
				$this->BatchProcess->start($processId);
				$settings = array();
				$settings['onBeforeGenerate'] = array('callback' => array($this->BatchProcess, 'check'), 'params' => array($processId));
				$settings['onError'] = array('callback' => array($this->BatchProcess, 'error'), 'params' => array($processId));
				
				/* Not required to run the indicator
				//$indicatorIds = $this->BatchProcess->field('reference_id', array('id' => $processId));
				
				$Indicator = new IndicatorComponent(new ComponentCollection);
				$Indicator->init();
				$log = $Indicator->run($settings);
				*/
				
				$componentObj = $format.'Component';
				App::uses($componentObj, $format.'.Controller/Component');
				$component = new $componentObj(new ComponentCollection);
				$component->init();
				$log = $component->export($settings);
				$this->BatchProcess->completed($processId, $log);
			} catch(Exception $ex) {
				echo $ex->getMessage() . "\n\n";
			}
		}
    }
}

?>
