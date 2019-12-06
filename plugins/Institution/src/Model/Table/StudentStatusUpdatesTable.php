<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StudentStatusUpdatesTable extends ControllerActionTable
{
    const MAX_PROCESSES = 1;
    const NOT_EXECUTED = 1;
    const EXECUTED = 2;
    public function initialize(array $config)
    {
        $this->table('student_status_updates');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses', 'foreignKey' => 'status_id']);

        // only allow index and view
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

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->field('model_reference', ['visible' => false]);
        $this->field('execution_status', ['before' => 'model']);
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
            'execution_status' => self::NOT_EXECUTED,
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
        $this->log('=======>Before triggerUpdateStudentStatusShell', 'debug');
        $this->triggerUpdateStudentStatusShell();
        $this->log(' <<<<<<<<<<======== After triggerUpdateStudentStatusShell', 'debug');
    }

    public function onGetExecutionStatus(Event $event, Entity $entity)
    {
        if ($entity->execution_status == self::NOT_EXECUTED) {
            $status = __('Not Executed');
        } elseif ($entity->execution_status == self::EXECUTED) {
            $status = __('Executed');
        }
        return '<span class="status highlight">'.$status.'</span>';
    }

    public function getStudentWithdrawalRecords($first = false)
    {
        $currentAcademicPeriod = $this->AcademicPeriods->getCurrent();
        $academicPeriodDetail = $this->AcademicPeriods->get($currentAcademicPeriod);
        $academicPeriodEffectiveDate = $academicPeriodDetail->start_date->format('Y-m-d');
        $academicPeriodEndDate = $academicPeriodDetail->end_date->format('Y-m-d');
       
        $studentWithdrawRecords = [];
        $today = Time::now();
        $today = $today->format('Y-m-d');
        
        if($academicPeriodEndDate >= $today && $academicPeriodEffectiveDate <= $today){
            Log::write('debug', 'End date');
            Log::write('debug', $academicPeriodEndDate);
             Log::write('debug', 'Start date');
             Log::write('debug', $academicPeriodEffectiveDate);
             Log::write('debug', 'Today date');
             Log::write('debug', $today);
            $query = $this
                ->find()
                ->where([
                    $this->aliasField('effective_date <= ') => $today,
                    $this->aliasField('execution_status') => self::NOT_EXECUTED
                ])
                ->order(['created' => 'asc']);
            if ($first) {
                $studentWithdrawRecords = $query->first();
            } else {
                $studentWithdrawRecords = $query->toArray();
            }        
        }
        
        return $studentWithdrawRecords;
    }

    public function triggerUpdateStudentStatusShell()
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
        $runningProcess = $SystemProcesses->getRunningProcesses($model);
        // should only have 1 process running
        if (count($runningProcess) < self::MAX_PROCESSES) {
            $args = $model;
            $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateStudentStatus '.$args;
            $logs = ROOT . DS . 'logs' . DS . 'UpdateStudentStatus.log & echo $!';
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
                $this->out('error : ' . __METHOD__ . ' exception when triggering UpdateStudentStatusShell: '. $e);
            }
        }
    }

    public function checkRequireUpdate()
    {
        $today = date('Y-m-d');
        $lastExectuedDate = null;
        $dir = new Folder(ROOT . DS . 'logs');
        $file = new File($dir->path. DS .'UpdateStudentStatus.log', true);
        $updateStudentStatus = json_decode($file->read());
        if (isset($updateStudentStatus[1])) {
            $lastExectuedDate = $updateStudentStatus[1];
        }
        if (is_null($lastExectuedDate) || $today > $lastExectuedDate) {
            $recordsToUpdate = count($this->getStudentWithdrawalRecords());
            if ($recordsToUpdate > 0) {
                $this->triggerUpdateStudentStatusShell();
            } else {
                Log::write('debug', 'No records to update');
            }
        } else {
            Log::write('debug', 'UpdateStudentStatusShell last executed on '.$lastExectuedDate);
        }
    }

    public function writeLastExecutedDateToFile(Event $event)
    {
        $today = date('Y-m-d');
        $passArray = [];
        $passArray = ['Last executed in '.$this->registryAlias(), $today];
        $message = json_encode($passArray);

        Log::write('debug', 'Writing last exceuted date ' .$today.' into tmp/UpdateStudentStatus');
        $dir = new Folder(ROOT . DS . 'tmp');
        $file = new File($dir->path . DS . 'UpdateStudentStatus', true);
        $file->write($message);
        $file->close();
    }
}
