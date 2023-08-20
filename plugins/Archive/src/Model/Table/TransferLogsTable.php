<?php

namespace Archive\Model\Table;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Core\Exception\Exception;
use Cake\Log\Log;
use Cake\Utility\Security;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;

/**
 * Class TransferLogsTable
 * @package Archive\Model\Table
 */
class TransferLogsTable extends ControllerActionTable
{

    /**
     * Initialize method
     *prd_cor_arc
     * @param array $config The configuration for the Table.
     * @return void
     */
    // for status
    private $statusOptions = [];

    CONST IN_PROGRESS = 1;
    CONST DONE = 2;
    CONST ERROR = 3;
    public static $ArchiveVars = ['Tables' =>
        ['StudentAttendances' => [
            'institution_class_attendance_records',
            'institution_student_absences',
            'institution_student_absence_details',
            'student_attendance_marked_records',
            'student_attendance_mark_types',
        ],
            'StaffAttendances' => [
                'institution_staff_attendances',
                'institution_staff_leave',
            ],
            'StudentAssessments' => [
                'assessment_item_results',
            ]
        ],
        'Shell' =>
            ['StudentAttendances' => 'ArchiveStudentAttendances',
                'StaffAttendances' => 'ArchiveStaffAttendances',
                'StudentAssessments' => 'ArchiveStudentAssessments']

    ];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('transfer_logs');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->belongsTo('AcademicPeriods', [
            'foreignKey' => 'academic_period_id',
            'joinType' => 'INNER',
            'className' => 'AcademicPeriod.AcademicPeriods'
        ]);

        $this->toggle('view', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->statusOptions = [
            self::IN_PROGRESS => __('Processing'),
            self::DONE => __('Completed'),
            self::ERROR => __('Error')
        ];


    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator->integer('id')->allowEmpty('id', 'create');
        $validator->dateTime('generated_on')->allowEmpty('generated_on', 'create');
        $validator->allowEmpty('generated_by', 'create');
        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['academic_period_id'], 'AcademicPeriods'));

        return $rules;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['sort' => true]);
        $this->field('generated_on');
        $this->field('generated_by');
        $this->field('p_id', ['visible' => false]);
        $this->field('features', ['sort' => false]); // POCOR-6816
        $this->setFieldOrder(['academic_period_id', 'features', 'generated_on', 'generated_by']);
        $alreadytransferring = $this->find('all')
            ->where(['process_status' => self::IN_PROGRESS,
            ])
            ->count();
        if ($alreadytransferring === 1) {
            $this->Alert->warning('There is an archive process currently running. Please try again later', ['type' => 'string', 'reset' => true]);;
        }
        if ($alreadytransferring > 1) {
            $this->Alert->warninf("There are $alreadytransferring archive processes currently running. Please try again later", ['type' => 'string', 'reset' => true]);;
        }
        //$this->Alert->info('Archive.backupReminder', ['reset' => false]);

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration', 'Archive', 'Archive');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
        $this->clearPendingProcesses();
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $condition = [$this->AcademicPeriods->aliasField('current') . ' <> ' => "1"];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        $this->field('academic_period_id', ['type' => 'select', 'options' => $academicPeriodOptions]);
        $this->field('features', ['type' => 'select', 'options' => $this->getFeatureOptions()]); // POCOR-6816
        $this->field('id', ['visible' => false]);
        $this->field('generated_on', ['visible' => false]);
        $this->field('generated_by', ['visible' => false]);
        $this->field('process_status', ['visible' => false]);
        $this->field('p_id', ['visible' => false]);

        $this->setFieldOrder(['academic_period_id', 'features']); // POCOR-6816

    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        //POCOR-6816
        $this->setFullyArchivedYearInactive();
        $this->setFullyArchivedYearInvisible();
        //POCOR-6816
    }

    /**
     * common proc to show related user name
     * @param Event $event
     * @param Entity $entity
     * @return mixed
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        $generated_by = self::getRelatedRecord('User.Users', $entity->generated_by);
        $name = $generated_by['name'];
        return $name;
    }


    /**
     * common proc to get the setting from Configuration
     * @return bool
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function hideAcademicPeriod()
    {
        $answer = true;
//        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
//        $question = $ConfigItems->value('archiving_hides_academic_period');
//        if ($question) {
//            $answer = ($question == "1") ? true : false;
//        }
        return $answer;
    }

    /**
     * common proc to get the setting from Configuration
     * @return bool
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function disableAcademicPeriod()
    {
        $answer = true;
//        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
//        $question = $ConfigItems->value('archiving_disables_academic_period');
//        if ($question) {
//            $answer = ($question == "1") ? true : false;
//        }
        return $answer;
    }

    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getRelatedRecord($tableName, $relatedField)
    {
        if (!$relatedField) {
            return null;
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            return '-';
        }
        return '+';
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        if ($entity->isNew()) {
            $superAdmin = $this->checkSuperAdmin();
            if (!$superAdmin) {
                $this->Alert->error('Archive.notSuperAdmin');
                $event->stopPropagation();
                return false;
            }
            $current = $this->isCurrent($entity);
            if ($current) {
                $this->Alert->error('Archive.currentAcademic');
                return false;
            }
            $alreadytransferring = $this->find('all')
                ->where(['process_status' => self::IN_PROGRESS,
                ])
                ->count();
            if ($alreadytransferring > 0) {
                $this->Alert->error('There is an archive process currently running. Please try again later', ['type' => 'string', 'reset' => true]);
//                $event->stopPropagation();
//                return false;
            }
            $entity->p_id = random_int(100000, 999999);
//        $entity->process_status = 0;
            $entity->academic_period_id = $entity['academic_period_id'];
            $entity->generated_on = date("Y-m-d H:i:s");
            $entity->generated_by = $this->Session->read('Auth.User.id');
            $entity->features = $entity['features'];
        }
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @param ArrayObject $data
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */

    public function afterSave(Event $event, Entity $entity, ArrayObject $data)
    {

//        $superAdmin = $this->checkSuperAdmin();
//        if (!$superAdmin) {
//            $this->Alert->error('Archive.notSuperAdmin');
//            return false;
//        }
//        $this->log('after save', 'debug');
        ini_set('memory_limit', '-1');
//        $this->log('after save' . $entity->features, 'debug');
        if ($entity->features == "Student Attendances") {
            $this->archiveStudentAttendances($entity);
        }
        if ($entity->features == "Staff Attendances") {
            $this->archiveStaffAttendances($entity);
        }
        if ($entity->features == "Student Assessments") {
            $this->archiveStudentAssessments($entity);
        }
    }

    /**
     * @param $shellName
     * @param null $academicPeriodId
     * @param null $pid
     * @param int $recordsToArchive
     * @param int $recordsInArchive
     */


    public function triggerArchiveShell($shellName, $academicPeriodId = null, $pid = null, $recordsToArchive = 0, $recordsInArchive = 0)
    {
        $this->log("=======>Before $shellName", 'debug');
        $args = '';
        $args .= !is_null($academicPeriodId) ? ' ' . $academicPeriodId : ' 0';
        $args .= !is_null($pid) ? ' ' . $pid : ' 0';
        $args .= !is_null($recordsToArchive) ? ' ' . $recordsToArchive : ' 0';
        $args .= !is_null($recordsInArchive) ? ' ' . $recordsInArchive : ' 0';

        $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $shellName . $args;
        $logs = ROOT . DS . 'logs' . DS . $shellName . '.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
        $this->log("<<<<<<<<<<======== After $shellName", 'debug');
    }


    public function triggerDatabaseTransferShell($shellName, $academicPeriodId = null)
    {
        $args = '';
        $args .= !is_null($academicPeriodId) ? ' ' . $academicPeriodId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $shellName . $args;
        $logs = ROOT . DS . 'logs' . DS . $shellName . '.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    /**
     * POCOR-6816
     * add features dropdown
     */
    public function getFeatureOptions()
    {
        $options = [
            'Student Attendances' => __('Student Attendances'),
            'Staff Attendances' => __('Staff Attendances'),
            'Student Assessments' => __('Student Assessments'),
        ];
        return $options;
    }

    /*
   * Function to show status on view page
   * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
   * return data
   * @ticket POCOR-7237
   */
    public function onGetProcessStatus(Event $event, Entity $entity)
    {
        if ($entity->process_status === self::IN_PROGRESS) {
            $value = $this->statusOptions[self::IN_PROGRESS];
        } elseif ($entity->process_status === self::DONE) {
            $value = $this->statusOptions[self::DONE];
        } elseif ($entity->process_status === self::ERROR) {
            $value = $this->statusOptions[self::ERROR];
        } else {
            $value = "";
        }
        return $value;
    }

    /**
     * @param Entity $entity
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * cleaner code
     * Archive following tables
     * institution_class_attendance_records
     * institution_student_absences
     * institution_student_absence_details
     * student_attendance_marked_records
     * student_attendance_mark_types
     */
    private function archiveStudentAttendances(Entity $entity)
    {
        $tablesToArchive = self::$ArchiveVars['Tables']['StudentAttendances'];
        $shellName = self::$ArchiveVars['Shell']['StudentAttendances'];
        $this->archiveTableRecords($entity, $tablesToArchive, $shellName);
    }

    /**
     * @param Entity $entity
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * cleaner code
     * Archive following tables
     * institution_staff_attendances
     * institution_staff_leave
     */
    private function archiveStaffAttendances(Entity $entity)
    {
        $tablesToArchive = self::$ArchiveVars['Tables']['StaffAttendances'];
        $shellName = self::$ArchiveVars['Shell']['StaffAttendances'];
        $this->archiveTableRecords($entity, $tablesToArchive, $shellName);
    }

    /**
     * @param Entity $entity
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * cleaner code
     * archive following tables
     * assessment_item_results
     */
    private function archiveStudentAssessments(Entity $entity)
    {
        $tablesToArchive = self::$ArchiveVars['Tables']['StudentAssessments'];
        $shellName = self::$ArchiveVars['Shell']['StudentAssessments'];
        $this->log(self::$ArchiveVars, 'debug');
        $this->log($tablesToArchive, 'debug');
        $this->log($shellName, 'debug');
        $this->archiveTableRecords($entity, $tablesToArchive, $shellName);
    }


    /**
     * @param Entity $entity , $tablesToArchive, $shellName
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * Archive table records
     */
    private function archiveTableRecords(Entity $entity, $tablesToArchive, $shellName)
    {
        $this->log($shellName, 'debug');
        $session = $this->Session;
        $superAdmin = $session->read('Auth.User.super_admin');

        if ($superAdmin == 1) {//POCOR-7399
            $academic_period_id = $entity->academic_period_id;
            $recordsToArchive = 0;
            foreach ($tablesToArchive as $tableToArchive) {
                $tableRecordsCount =
                    self::getTableRecordsCountForAcademicPeriod($tableToArchive,
                        $academic_period_id);
                $recordsToArchive = $recordsToArchive + $tableRecordsCount;
            }

            if ($recordsToArchive == 0) {
//                $this->log($entity, 'debug');
                $entity['process_status'] = self::DONE;
                $entity->features = $entity['features'] . '. ' . __('No Records');
                $this->save($entity);
                $this->Alert->error('Connection.noDataToArchive', ['reset' => true]);
            }

            if ($recordsToArchive > 0) {
                $recordsInArchive = 0;
                foreach ($tablesToArchive as $tableToArchive) {
                    $archive_table_name = ArchiveConnections::hasArchiveTable($tableToArchive);
                    $archiveTableRecordsCount =
                        self::getTableRecordsCountForAcademicPeriod($archive_table_name,
                            $academic_period_id);
                    $recordsInArchive = $recordsInArchive + $archiveTableRecordsCount;
                }

                $recordsInArchiveStr = number_format($recordsInArchive, 0, '', ' ');
                $recordsToArchiveStr = number_format($recordsToArchive, 0, '', ' ');
                $todoing = trim($entity['features']) . '. ' . $recordsToArchiveStr . '/' . $recordsInArchiveStr;

                $alreadytransferring = $this->find('all')
                    ->where(['academic_period_id' => $entity->academic_perid_id,
                        'process_status' => self::IN_PROGRESS,
                        'p_id !=' => $entity->p_id
                    ])
                    ->count();
                if ($alreadytransferring > 0) {
                    $this->Alert->error('There is an archive process currently running. Please try again later', ['type' => 'string', 'reset' => true]);
                }
                $this->triggerArchiveShell($shellName, $academic_period_id, $entity->p_id, $recordsToArchive, $recordsInArchive);
                $entity->features = $todoing;
                $entity->process_status = self::IN_PROGRESS;
                $this->save($entity);
            }
        }
        if ($superAdmin != 1) {
            $this->Alert->error('Connection.testConnectionFail', ['reset' => true]);
        }
    }

    public static
    function setTransferLogsFailed($pid)
    {
        $TransferLogs = TableRegistry::get('Archive.TransferLogs');
        $TransferLogs->updateAll(['process_status' => $TransferLogs::ERROR],
            ['p_id' => $pid]
        );
        $processInfo = date('Y-m-d H:i:s');
        return $processInfo;
    }

    /**
     * @param $systemProcessId
     */
    public static
    function setSystemProcessFailed($systemProcessId)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
    }

    /**
     * @param $systemProcessId
     */
    public static
    function setSystemProcessCompleted($systemProcessId)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::COMPLETED);
        $processInfo = date('Y-m-d H:i:s');
        return $processInfo;
    }

    /**
     * @param $table_name
     * @param $academic_period_id
     * @return int
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * cleaner code
     */
    private static function getTableRecordsCountForAcademicPeriod($table_name, $academic_period_id)
    {
        $connectionName = 'default';
        $fieldName = 'academic_period_id';
        $RecordsCount = self::getSimpleCount(
            $table_name,
            $connectionName,
            $fieldName,
            $academic_period_id);
        return intval($RecordsCount);
    }

    private static function getSimpleCount($tableName, $connectionName, $fieldName, $fieldValue) {
        $connection = ConnectionManager::get($connectionName);
        $sql = "SELECT count(*) as count FROM $tableName WHERE $fieldName = :fieldValue";
        $result = $connection->execute($sql, ['fieldValue' => $fieldValue])->fetch('assoc');
        return intval($result['count']);
    }


    private function setFullyArchivedYearInactive()
    {
        $setting = self::disableAcademicPeriod();
        if (!$setting) {
            return;
        }
        $condition = [$this->AcademicPeriods->aliasField('current') . ' <> ' => "1"];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        foreach ($academicPeriodOptions AS $key => $val) {
            $transferLogdata = $this->find('all')
                ->where(['academic_period_id' => $key,
                    'process_status' => $this::DONE])
                ->toArray();
            $getFeatureOptionsCount = count($this->getFeatureOptions());
            if ($getFeatureOptionsCount == count($transferLogdata)) {
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $AcademicPeriods->updateAll(
                    ['editable' => 0],    //field
                    ['id' => $key, 'current' => 0] //condition
                );
            }
        }
    }

    private function setFullyArchivedYearInvisible()
    {
        $setting = self::hideAcademicPeriod();
        if (!$setting) {
            return;
        }
        $condition = [$this->AcademicPeriods->aliasField('current') . ' <> ' => "1"];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        foreach ($academicPeriodOptions AS $key => $val) {
            $transferLogdata = $this->find('all')
                ->where(['academic_period_id' => $key,
                    'process_status' => $this::DONE])->toArray();
            $getFeatureOptionsCount = count($this->getFeatureOptions());
            if ($getFeatureOptionsCount == count($transferLogdata)) {
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $AcademicPeriods->updateAll(
                    ['visible' => 0],    //field
                    ['id' => $key, 'current' => 0] //condition
                );
            }
        }
    }

    /**
     * @return mixed
     */
    private function checkSuperAdmin()
    {
        $session = $this->Session;
        $superAdmin = $session->read('Auth.User.super_admin');
        return $superAdmin;
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    private function isCurrent(Entity $entity)
    {
        $academic_period_id = $entity['academic_period_id'];
        $current_academic_period_id = $this->AcademicPeriods->getCurrent();
        $current = $academic_period_id == $current_academic_period_id;
        return $current;
    }

    /**
     * @param $php_process_id
     * @return bool
     */
    private static function isPhpProcessRunning($php_process_id)
    {
        return posix_kill($php_process_id, 0) && posix_getsid($php_process_id) !== false;
    }

    private function clearPendingProcesses()
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $runningProcess = $SystemProcesses->getRunningProcesses($this->registryAlias());
        foreach ($runningProcess as $key => $processData) {
            $process_params = (array)json_decode($processData['params']);
            $systemProcessId = $processData['id'];
            $transfer_log_pid = isset($process_params['pid']) ? $process_params['pid'] : null;
            $process_academic_period_id = isset($process_params['academic_period_id']) ? $process_params['academic_period_id'] : null;
            $php_process_id = isset($processData['process_id']) ? $processData['process_id'] : 0;
            $isPhpProcessRunning = self::isPhpProcessRunning($php_process_id);
            if ($transfer_log_pid == null) {
                $this->log("gonna kill $systemProcessId", 'debug');
                if ($isPhpProcessRunning) {
                    $SystemProcesses::killProcess($php_process_id);
                    self::setSystemProcessFailed($systemProcessId);
                }
                if (!$isPhpProcessRunning) {
                    self::setSystemProcessFailed($systemProcessId);
                }
            }
            if ($transfer_log_pid != null) {
                $this->log("not gonna kill $systemProcessId", 'debug');
                if (!$isPhpProcessRunning) {
                    self::setTransferLogsFailed($transfer_log_pid);
                    self::setSystemProcessFailed($systemProcessId);
                }
            }
        }
    }

}
