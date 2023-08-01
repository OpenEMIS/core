<?php

namespace Archive\Model\Table;

use Cake\Datasource\Exception\RecordNotFoundException;
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
        $this->field('academic_period_id');
        $this->field('generated_on');
        $this->field('generated_by');
        $this->field('p_id', ['visible' => false]);
        $this->field('features', ['sort' => false]); // POCOR-6816 
        $this->setFieldOrder(['academic_period_id', 'features', 'generated_on', 'generated_by']);

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
        $answer = false;
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $question = $ConfigItems->value('archiving_hides_academic_period');
        if ($question) {
            $answer = ($question == "1") ? true : false;
        }
        return $answer;
    }

    /**
     * common proc to get the setting from Configuration
     * @return bool
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function disableAcademicPeriod()
    {
        $answer = false;
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $question = $ConfigItems->value('archiving_disables_academic_period');
        if ($question) {
            $answer = ($question == "1") ? true : false;
        }
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
        $superAdmin = $this->checkSuperAdmin();
        if (!$superAdmin) {
            $this->Alert->error('Archive.notSuperAdmin');
            return false;
        }
        $current = $this->isCurrent($entity);
        if ($current) {
            $this->Alert->error('Archive.currentAcademic');
            return false;
        }
        $entity->p_id = getmypid();
        $entity->process_status = self::IN_PROGRESS;
        $entity->academic_period_id = $entity['academic_period_id'];
        $entity->generated_on = date("Y-m-d H:i:s");
        $entity->generated_by = $this->Session->read('Auth.User.id');
        $entity->features = $entity['features'];
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @param ArrayObject $data
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */

    public function afterSave(Event $event, Entity $entity, ArrayObject $data){

        $superAdmin = $this->checkSuperAdmin();
        if (!$superAdmin) {
            $this->Alert->error('Archive.notSuperAdmin');
            return false;
        }
        ini_set('memory_limit', '-1');
        if($entity->features == "Student Attendances"){
            $this->archiveStudentAttendances($entity);
        }
        if($entity->features == "Staff Attendances"){
            $this->archiveStaffAttendances($entity);
        }
        if($entity->features == "Student Assessments"){
            $this->archiveStudentAssessments($entity);
        }
    }

    /**
     * @param $shellName
     * @param null $academicPeriodId
     * @param null $pid
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     *
     */

    public function triggerArchiveShell($shellName, $academicPeriodId = null, $pid = null)
    {
        $this->log("=======>Before $shellName", 'debug');
        $args = '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';
        $args .= !is_null($pid) ? ' '.$pid : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
        $this->log("<<<<<<<<<<======== After $shellName", 'debug');
    }


    public function triggerDatabaseTransferShell($shellName,$academicPeriodId = null)
    {
        $args = '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
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
            'Student Attendance' => __('Student Attendance'),
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
        if ($entity->process_status == 1) {
            $value = $this->statusOptions[self::IN_PROGRESS];
        } elseif ($entity->process_status == 2) {
            $value = $this->statusOptions[self::DONE];
        } elseif ($entity->process_status == 3) {
            $value = $this->statusOptions[self::DONE];
        } else {
            $value = $this->statusOptions[self::DONE];
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
        $tablesToArchive = [
            'institution_class_attendance_records',
            'institution_student_absences',
            'institution_student_absence_details',
            'student_attendance_marked_records',
            'student_attendance_mark_types',
        ];
        $shellName = "ArchiveStudentAttendances";
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
        $tablesToArchive = [
            'institution_staff_attendances',
            'institution_staff_leave',
        ];
        $shellName = "ArchiveStaffAttendances";
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
        $tablesToArchive = [
            'assessment_item_results',
        ];
        $shellName = "ArchiveStudentAssessments";
        $this->archiveTableRecords($entity, $tablesToArchive, $shellName);
    }


    /**
     * @param Entity $entity, $tablesToArchive, $shellName
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * Archive table records
     */
    private function archiveTableRecords(Entity $entity, $tablesToArchive, $shellName)
    {
        $session = $this->Session;
        $superAdmin = $session->read('Auth.User.super_admin');
        if ($superAdmin == 1) {//POCOR-7399
            $academic_period_id = $entity->academic_period_id;
            $recordsToArchive = 0;
            $tableRecordsCount = 0;
            foreach ($tablesToArchive as $tableToArchive){
                $tableRecordsCount =
                    $this->getTableRecordsCountForAcademicPeriod($tableToArchive,
                        $academic_period_id);
                $recordsToArchive = $recordsToArchive + $tableRecordsCount;
                $tableRecordsCount = 0;
            }

            if ($recordsToArchive == 0) {
                $this->deleteAll([
                    $this->aliasField('p_id') => $entity->p_id
                ]);

                $this->Alert->error('Connection.noDataToArchive', ['reset' => true]);
            }
            if ($recordsToArchive > 0) {
                $this->triggerArchiveShell($shellName, $academic_period_id, $entity->p_id);
            }
        }
        if ($superAdmin != 1) {
            $this->Alert->error('Connection.testConnectionFail', ['reset' => true]);
        }
    }

    /**
     * @param $table_name
     * @param $academic_period_id
     * @return int
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * cleaner code
     */
    private function getTableRecordsCountForAcademicPeriod($table_name, $academic_period_id)
    {
        $Table = TableRegistry::get($table_name);
        $RecordsCount = $Table->find('all')
            ->where(['academic_period_id' => $academic_period_id])->count();
        return intval($RecordsCount);
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

}
