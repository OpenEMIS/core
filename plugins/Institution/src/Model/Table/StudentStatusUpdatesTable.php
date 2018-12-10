<?php
namespace Institution\Model\Table;

use Cake\ORM\Entity;
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
    const EXECUTED = 1;
    const NOT_EXECUTED = 2;
	public function initialize(array $config)
    {
        $this->table('student_status_updates');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses', 'foreignKey' => 'status_id']);

        // only allow index and edit
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
	}

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Students.afterDelete'] = 'studentsAfterDelete';
        $events['Shell.StudentWithdraw.writeLastExecutedDateToFile'] = 'writeLastExecutedDateToFile';
        return $events;
    }

    public function studentsAfterDelete(Event $event, Entity $student)
    {
        $this->removePendingWithdraw($student);
    }

    protected function removePendingWithdraw($student)
    {
        $conditions = [
            'security_user_id' => $student->student_id,
            'institution_id' => $student->institution_id,
            'education_grade_id' => $student->education_grade_id,
            'academic_period_id' => $student->academic_period_id,
        ];

        $entity = $this
                ->find()
                ->where(
                    $conditions
                )
                ->first();

        if (!empty($entity)) {
            $this->delete($entity);
        }
    }

    public function afterSave()
    {
        Log::write('debug', 'in afterSave');
        $this->triggerUpdateWithdrawalStudentShell();
    }

    public function getStudentWithdrawalRecords($first = false)
    {
        $StudentWithdraw = TableRegistry::get('Institution.StudentWithdraw');
        $today = Time::now();
        $query = $this
            ->find()
            ->where([
                $this->aliasField('effective_date <= ') => $today,
                $this->aliasField('model') => $StudentWithdraw->alias(),
                $this->aliasField('execution_status') => 1
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
