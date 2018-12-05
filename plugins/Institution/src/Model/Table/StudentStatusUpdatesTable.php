<?php
namespace Institution\Model\Table;

use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StudentStatusUpdatesTable extends ControllerActionTable
{
    const MAX_PROCESSES = 1;
	public function initialize(array $config)
    {
        $this->table('student_status_updates');
        parent::initialize($config);
	}

    public function afterSave()
    {
        $this->triggerUpdateWithdrawalStudentShell();
    }

    public function triggerUpdateWithdrawalStudentShell()
    {
        // model - StudentStatusUpdates
        $model = $this->registryAlias();
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $runningProcess = $SystemProcesses->getRunningProcesses($model);
        Log::write('debug', 'runningProcess >>>>>>>>>>>>>>>>> ');
        Log::write('debug', $runningProcess);

        foreach ($runningProcess as $key => $processData) {
            $systemProcessId = $processData['id'];
            $pId = !empty($processData['process_id']) ? $processData['process_id'] : 0;
            $createdDate = $processData['created'];

            $expiryDate = clone($createdDate);
            $expiryDate->addMinutes(30);
            $today = Time::now();
            // purge
            if ($expiryDate < $today) {
                $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::COMPLETED);
                $SystemProcesses->killProcess($pId);
            }
        }
        // should only have 1 process running
        if (count($runningProcess) < self::MAX_PROCESSES) {
            $processModel = $model;
            $passArray = [
                'institution_id' => $entity->institution_id
            ];
            $params = json_encode($passArray);

            $args = $processModel . " " . $params;
            $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateWithdrawalStudent '.$args;
            $logs = ROOT . DS . 'logs' . DS . 'UpdateWithdrawalStudent.log & echo $!';
            Log::write('debug', '$args');
            Log::write('debug', $args);
            Log::write('debug', '$cmd');
            Log::write('debug', $cmd);
            Log::write('debug', '$logs');
            Log::write('debug', $logs);
            $shellCmd = $cmd . ' >> ' . $logs;
            Log::write('debug', $shellCmd);
            try {
                Log::write('debug', 'Triggering shell');
                $pid = exec($shellCmd);
                Log::write('debug', $pid);
            } catch(\Exception $e) {
                $this->out('error : ' . __METHOD__ . ' exception when triggering UpdateWithdrawalStudentShell: '. $e);
            }
        }
    }
}
