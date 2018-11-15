<?php
namespace App\Shell;

use Exception;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

class UpdateWithdrawalStudentShell extends Shell {
	const NEW_PROCESS = 1;
	const RUNNING = 2;
	const COMPLETED = 3;
	const ERROR = -2;
	const SLEEP_TIME = 86400; //need to change this value to 86400

	public function initialize() {
		parent::initialize();
		$this->loadModel('Institution.StudentWithdraw');
        $this->loadModel('Institution.Students');
        $this->loadModel('SystemProcesses');
	}

 	public function main() {
 		try {
 			$today = Time::now();
 			$model = $this->StudentWithdraw->registryAlias();
 			$pid = getmypid();
	 		// Ensure that no process is running currently. Max number of shell: 1

	 		$systemProcessId = $this->SystemProcesses
	 			->find()
	 			->where([
	 				$this->SystemProcesses->aliasField('model') => $model
	 			])
	 			->extract('id')
	 			->first();

	 		if ($systemProcessId) {
	 			$this->out('Process exists in system_processes >>>>>>>>>>>> ');
	 			$this->out('Check and purge stuck processes >>>>>>>>>>>> ');
	 			$this->_totalRunningSystemProcesses($model, $today);
	 			$this->out('Updating pid >>>>>>>>>>>> ');
                $this->SystemProcesses->updatePid($systemProcessId, $pid);
                $this->out('Updating process status to NEW_PROCESS >>>>>>>>>>>> ');
	 			$this->SystemProcesses->updateProcess($systemProcessId, Time::now(), self::NEW_PROCESS);
	 		} else {
	 			$this->out('Process does not exists in system_processes. Adding new process >>>>>>>>>>>>');
 				$systemProcessId = $this->SystemProcesses->addProcess('UpdateWithdrawalStudent', getmypid(), $model);
        	}
	 		// die;
 			$studentWithdrawRecords = $this->getStudentWithdrawRecords($today);
 			$this->out('Retrieving student withdrawal records >>>>>>>>>>>> ');
 			if (!empty($studentWithdrawRecords)) {
 				$this->out('Student withdrawal records found....');
 				$this->out('Updating process status to RUNNING >>>>>>>>>>>> ');
 				$this->SystemProcesses->updateProcess($systemProcessId, Time::now(), self::RUNNING);
 				foreach ($studentWithdrawRecords as $key => $StudentWithdrawRecord) {
 					$studentWithdrawRecordId = $StudentWithdrawRecord->id;
					$this->out("Dispatching event to update student status to withdraw for StudentWithdrawTable:" . $studentWithdrawRecordId);
					$event = $this->StudentWithdraw->dispatchEvent('StudentWithdraw.onApproval', [$studentWithdrawRecordId]);
					if ($event->isStopped()) {
						$this->out('stopped');
					}
					$this->out("End dispatch event for recordId ".$studentWithdrawRecordId);
 				}
 				$this->out('Updating system process to COMPLETED status');
 				$this->SystemProcesses->updateProcess($systemProcessId, Time::now(), self::COMPLETED);
 				// $this->SystemProcesses->killProcess($pid);
 				$this->_recursiveCall();
 			} else {
 				// $this->SystemProcesses->killProcess($pid);
 				$this->_recursiveCall();
 			}
 		} catch (\Exception $e) {
			$this->out('Exception Caught');
			$this->out($e->getMessage());
			// should log the message here with the date and $e->getMessage
			$this->SystemProcesses->updateProcess($systemProcessId, Time::now(), self::ERROR);
 		}
	}

	private function _recursiveCall()
	{
        sleep(self::SLEEP_TIME);
        $this->out('recursive calling self');
        $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateWithdrawalStudent';
        $logs = ROOT . DS . 'logs' . DS . 'UpdateWithdrawalStudent.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
        } catch(\Exception $e) {
            $this->out('error : ' . __METHOD__ . ' exception when _recursiveCall : '. $e);
        }
        exit();
    }

    private function getStudentWithdrawRecords($today)
    {
		$studentWithdrawRecords = $this->StudentWithdraw
			->find()
			->innerJoin(
				[$this->Students->alias() => $this->Students->table()],
				[$this->Students->aliasField('academic_period_id = ') . $this->StudentWithdraw->aliasField('academic_period_id'),
                $this->Students->aliasField('education_grade_id = ') . $this->StudentWithdraw->aliasField('education_grade_id'),
                $this->Students->aliasField('student_id = ') . $this->StudentWithdraw->aliasField('student_id'),
                $this->Students->aliasField('institution_id = ') . $this->StudentWithdraw->aliasField('institution_id')]
            )
			->where([
				$this->Students->aliasField('student_status_id <> 4'),
				$this->StudentWithdraw->aliasField('effective_date') => $today,
				$this->StudentWithdraw->aliasField('status_id') => 76, // hard code to get the Withdrawn workflow status id first
			])
			->toArray()
			;
        return $studentWithdrawRecords;
    }

	private function _totalRunningSystemProcesses($model, $today)
    {
    	$this->out('Retrieving runningProcess >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> ');
    	// this will always return one record
        $runningProcess = $this->SystemProcesses->getRunningProcesses($model);
        if (!empty($runningProcess)) {
	        foreach ($runningProcess as $key => $processData) {
	            $systemProcessId = $processData['id'];
	            $pid = !empty($processData['process_id']) ? $processData['process_id'] : 0;
	            $createdDate = $processData['created'];

	            if ($createdDate < $today) {
	            	$this->out('today is greater than createdDate');
	            	$this->out('Proceeds to update the process to completed');
	                $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), self::COMPLETED);
	                $this->out('Proceeds to kill the process with pId'. $pid);
	                $this->SystemProcesses->killProcess($pid);
	            }
	        }
        }
    }
}