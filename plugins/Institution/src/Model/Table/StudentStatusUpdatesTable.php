<?php
namespace Institution\Model\Table;

use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Shell.StudentWithdraw.writeLastExecutedDateToFile'] = 'writeLastExecutedDateToFile';
        return $events;
    }

    public function afterSave()
    {
        $this->triggerUpdateWithdrawalStudentShell();
    }

    public function getStudentWithdrawalRecords($first = false)
    {
        $today = Time::now();
        $query = $this
            ->find()
            ->where([
                $this->aliasField('effective_date <= ') => $today,
                $this->aliasField('model') => $this->alias(),
            ])
            ->order(['created' => 'asc']);
        if ($first) {
            $studentWithdrawRecords = $query->first();
        } else {
            $studentWithdrawRecords = $query->toArray();
        }
        return $studentWithdrawRecords;
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
            $args = $model;
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

    public function checkRequireUpdate()
    {
        $today = date('Y-m-d');
        $dir = new Folder(ROOT . DS . 'tmp');
        $file = new File($dir->path.'/UpdateWithdrawalStudent', true);
        $updateWithdrawalStudent = json_decode($file->read());
        $lastExectuedDate = $updateWithdrawalStudent[1];
        if (is_null($lastExectuedDate) || $today > $lastExectuedDate) {
            $recordsToUpdate = count($this->getStudentWithdrawalRecords());
            if ($recordsToUpdate > 0) {
                $this->triggerUpdateWithdrawalStudentShell();
            } else {
                Log::write('debug', 'No records to update');
            }
        } else {
            Log::write('debug', 'UpdateWithdrawalStudentShell last executed on '.$lastExectuedDate);
        }
    }

    public function writeLastExecutedDateToFile(Event $event)
    {
        $today = date('Y-m-d');
        $passArray = [];
        $passArray = ['Last executed in '.$this->registryAlias(), $today];
        $message = json_encode($passArray);

        Log::write('debug', 'Writing last exceuted date ' .$today.' into tmp/UpdateWithdrawalStudent');
        $dir = new Folder(ROOT . DS . 'tmp');
        $file = new File($dir->path.'/UpdateWithdrawalStudent', true);
        $file->write($message);
        $file->close();
    }
}
