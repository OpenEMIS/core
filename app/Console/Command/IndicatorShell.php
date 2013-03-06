<?php
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
