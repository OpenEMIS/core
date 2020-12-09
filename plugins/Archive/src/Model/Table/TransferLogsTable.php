<?php
namespace Archive\Model\Table;

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
use Cake\I18n\Time;

/**
 * DeletedLogs Model
 *
 * @property \Cake\ORM\Association\BelongsTo $AcademicPeriods
 *
 * @method \Archive\Model\Entity\DeletedLog get($primaryKey, $options = [])
 * @method \Archive\Model\Entity\DeletedLog newEntity($data = null, array $options = [])
 * @method \Archive\Model\Entity\DeletedLog[] newEntities(array $data, array $options = [])
 * @method \Archive\Model\Entity\DeletedLog|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Archive\Model\Entity\DeletedLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Archive\Model\Entity\DeletedLog[] patchEntities($entities, array $data, array $options = [])
 * @method \Archive\Model\Entity\DeletedLog findOrCreate($search, callable $callback = null, $options = [])
 */class TransferLogsTable extends ControllerActionTable
{

    /**
     * Initialize method
     *prd_cor_arc
     * @param array $config The configuration for the Table.
     * @return void
     */
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

    /*public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }*/

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id');    
        $this->field('generated_on');
        $this->field('generated_by');

        $this->setFieldOrder(['academic_period_id','generated_on','generated_by']);

        //$this->Alert->info('Archive.backupReminder', ['reset' => false]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        
        $this->field('id', ['visible' => false]);
        $this->field('generated_on', ['visible' => false]);
        $this->field('generated_by', ['visible' => false]);
        
        $this->setFieldOrder(['academic_period_id']);

    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $this->Alert->info('Archive.backupReminder');
        try {
            $connection = ConnectionManager::get('prd_cor_arc');
            $connected = $connection->connect();

        }catch (Exception $connectionError) {
            $this->Alert->warning('Connection.transferConnectionFail');
        }
    }

    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        $Users = TableRegistry::get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name','last_name'])
            ->where(['id' => $entity->generated_by])
            ->first();

        return $entity->generated_by = $result->first_name.' '.$result->last_name;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data){
        $default_connection = ConnectionManager::get('default');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');
        $StudentAbsences = TableRegistry::get('Report.StudentAbsences');

        $InstitutionStudentAbsenceDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $StudentAttendanceMarkType = TableRegistry::get('Attendance.StudentAttendanceMarkTypesTable');
        // echo $StudentAttendanceMarkType->alias();exit;
        //institution_student_absence_days -- couldn't find model regarding this table in master branch

        $academicPeriodId = 28;

        $allData = $AssessmentItemResults->find('all')
                                    ->select([
                                       'AssessmentItemResults.id','AssessmentItemResults.marks','AssessmentItemResults.assessment_grading_option_id','AssessmentItemResults.student_id','AssessmentItemResults.assessment_id','AssessmentItemResults.education_subject_id','AssessmentItemResults.education_grade_id','AssessmentItemResults.academic_period_id','AssessmentItemResults.assessment_period_id','AssessmentItemResults.institution_id','AssessmentItemResults.modified_user_id','AssessmentItemResults.modified','AssessmentItemResults.created_user_id','AssessmentItemResults.created'
                                    ])
                                    ->where([
                                        'AssessmentItemResults.academic_period_id' => $academicPeriodId
                                    ])
                                    ->limit(10)
                                    ->toArray();
        $connection = ConnectionManager::get('prd_cor_arc');                                
        foreach($allData AS $data){
            $newDate = date('Y-m-d H:i:s');
            if(isset($data["modified_user_id"])){
                $val = $data["modified_user_id"];
            }else{
                $val = 'NULL';
            }
            if(isset($data["modified"])){
                $val1 = $data["modified"];
            }else{
                $val1 = 'NULL';
            }

            if(!empty($data && isset($data))){
                try{
                    $data_inserted_successfully = 0;
                    $select_statement = $connection->prepare("select * from assessment_item_results where id = :id");
                    $select_statement->execute(array(':id' => $data["id"]));
                    $row = $select_statement->fetch();
                    if(empty($row)){
                        $statement = $connection->prepare('INSERT INTO assessment_item_results (id, 
                        marks, 
                        assessment_grading_option_id,
                        student_id,
                        assessment_id,
                        education_subject_id,
                        education_grade_id,
                        academic_period_id,
                        assessment_period_id,
                        institution_id,
                        modified_user_id,
                        modified,
                        created_user_id,
                        created)
                        
                        VALUES (:id, 
                        :marks, 
                        :assessment_grading_option_id,
                        :student_id,
                        :assessment_id,
                        :education_subject_id,
                        :education_grade_id,
                        :academic_period_id,
                        :assessment_period_id,
                        :institution_id,
                        :modified_user_id,
                        :modified,
                        :created_user_id,
                        :created)');

                        $statement->execute([
                        'id' => $data["id"],
                        'marks' => $data["marks"],
                        'assessment_grading_option_id' => $data["assessment_grading_option_id"],
                        'student_id' => $data["student_id"],
                        'assessment_id' => $data["assessment_id"],
                        'education_subject_id' => $data["education_subject_id"],
                        'education_grade_id' => $data["education_grade_id"],
                        'academic_period_id' => $data["academic_period_id"],
                        'assessment_period_id' => $data["assessment_period_id"],
                        'institution_id' => $data["institution_id"],
                        'modified_user_id' => isset($data["modified_user_id"]) ? $data["modified_user_id"] : NULL,
                        'modified' => isset($data["modified"]) ? $newDate : NULL,
                        'created_user_id' => $data["created_user_id"],
                        'created' => $newDate,
                        ]);
                        $data_inserted_successfully = 1;
                        $delete_statement = $default_connection->prepare("delete from assessment_item_results where id = :id");
                        $delete_statement->execute(array(':id' => $data["id"]));
                        
                    }
                    else{
                        echo "Already archived";
                    }
                
                }catch (PDOException $e) {
                    
                }
                
            }
        }
        $default_connection->execute('DELETE FROM assessment_item_results WHERE academic_period_id = 28 ');
        echo "<pre>";print_r($allData);exit;
         //get archive database connection
         $connection = ConnectionManager::get('prd_cor_arc');
        
         if(!empty($allData) && isset($allData)){
            foreach($allData as $data){
                if(isset($data['AssessmentItemResults']["modified_user_id"])){
                    $val = $data['AssessmentItemResults']["modified_user_id"];
                }else{
                    $val = 'NULL';
                }

                if(!empty($data['AssessmentItemResults'] && isset($data['AssessmentItemResults']))){
                    try{
                    $statement = $connection->prepare('INSERT INTO assessment_item_results (id, 
                    marks, 
                    assessment_grading_option_id,
                    student_id,
                    assessment_id,
                    education_subject_id,
                    education_grade_id,
                    academic_period_id,
                    assessment_period_id,
                    institution_id,
                    modified_user_id,
                    modified,
                    created_user_id,
                    created)
                    
                    VALUES (:id, 
                    :marks, 
                    :assessment_grading_option_id,
                    :student_id,
                    :assessment_id,
                    :education_subject_id,
                    :education_grade_id,
                    :academic_period_id,
                    :assessment_period_id,
                    :institution_id,
                    :modified_user_id,
                    :modified,
                    :created_user_id,
                    :created)');

                    $statement->execute([
                    'id' => $data['AssessmentItemResults']["id"],
                    'marks' => $data['AssessmentItemResults']["marks"],
                    'assessment_grading_option_id' => $data['AssessmentItemResults']["assessment_grading_option_id"],
                    'student_id' => $data['AssessmentItemResults']["student_id"],
                    'assessment_id' => $data['AssessmentItemResults']["assessment_id"],
                    'education_subject_id' => $data['AssessmentItemResults']["education_subject_id"],
                    'education_grade_id' => $data['AssessmentItemResults']["education_grade_id"],
                    'academic_period_id' => $data['AssessmentItemResults']["academic_period_id"],
                    'assessment_period_id' => $data['AssessmentItemResults']["assessment_period_id"],
                    'institution_id' => $data['AssessmentItemResults']["institution_id"],
                    'modified_user_id' => isset($data['AssessmentItemResults']["modified_user_id"]) ? $data['AssessmentItemResults']["modified_user_id"] : NULL,
                    'modified' => $data['AssessmentItemResults']["modified"],
                    'created_user_id' => $data['AssessmentItemResults']["created_user_id"],
                    'created' => $data['AssessmentItemResults']["created"],
                    ]);
                    }catch (PDOException $e) {
                        
                    }
                    
                }

                if(!empty($data['ClassAttendanceRecords'] && isset($data['ClassAttendanceRecords']))){
                    try{
                        $statement = $connection->prepare('INSERT INTO institution_class_attendance_records (institution_class_id,
                        academic_period_id,
                        year,
                        month,
                        day_1,
                        day_2,
                        day_3,
                        day_4,
                        day_5,
                        day_6,
                        day_7,
                        day_8,
                        day_9,
                        day_10,
                        day_11,
                        day_12,
                        day_13,
                        day_14,
                        day_15,
                        day_16,
                        day_17,
                        day_18,
                        day_19,
                        day_20,
                        day_21,
                        day_22,
                        day_23,
                        day_24,
                        day_25,
                        day_26,
                        day_27,
                        day_28,
                        day_29,
                        day_30,
                        day_31)
                        
                        VALUES (:institution_class_id, 
                        :academic_period_id,
                        :year,
                        :month,
                        :day_1,
                        :day_2,
                        :day_3,
                        :day_4,
                        :day_5,
                        :day_6,
                        :day_7,
                        :day_8,
                        :day_9,
                        :day_10,
                        :day_11,
                        :day_12,
                        :day_13,
                        :day_14,
                        :day_15,
                        :day_16,
                        :day_17,
                        :day_18,
                        :day_19,
                        :day_20,
                        :day_21,
                        :day_22,
                        :day_23,
                        :day_24,
                        :day_25,
                        :day_26,
                        :day_27,
                        :day_28,
                        :day_29,
                        :day_30,
                        :day_31)');
    
                        $statement->execute([
                        'institution_class_id' => $data['ClassAttendanceRecords']["institution_class_id"],
                        'academic_period_id' => $data['ClassAttendanceRecords']["academic_period_id"],
                        'year' => $data['ClassAttendanceRecords']["year"],
                        'month' => $data['ClassAttendanceRecords']["month"],
                        'day_1' => $data['ClassAttendanceRecords']["day_1"],
                        'day_2' => $data['ClassAttendanceRecords']["day_2"],
                        'day_3' => $data['ClassAttendanceRecords']["day_3"],
                        'day_4' => $data['ClassAttendanceRecords']["day_4"],
                        'day_5' => $data['ClassAttendanceRecords']["day_5"],
                        'day_6' => $data['ClassAttendanceRecords']["day_6"],
                        'day_7' => $data['ClassAttendanceRecords']["day_7"],
                        'day_8' => $data['ClassAttendanceRecords']["day_8"],
                        'day_9' => $data['ClassAttendanceRecords']["day_9"],
                        'day_10' => $data['ClassAttendanceRecords']["day_10"],
                        'day_11' => $data['ClassAttendanceRecords']["day_11"],
                        'day_12' => $data['ClassAttendanceRecords']["day_12"],
                        'day_13' => $data['ClassAttendanceRecords']["day_13"],
                        'day_14' => $data['ClassAttendanceRecords']["day_14"],
                        'day_15' => $data['ClassAttendanceRecords']["day_15"],
                        'day_16' => $data['ClassAttendanceRecords']["day_16"],
                        'day_17' => $data['ClassAttendanceRecords']["day_17"],
                        'day_18' => $data['ClassAttendanceRecords']["day_18"],
                        'day_19' => $data['ClassAttendanceRecords']["day_19"],
                        'day_20' => $data['ClassAttendanceRecords']["day_20"],
                        'day_21' => $data['ClassAttendanceRecords']["day_21"],
                        'day_22' => $data['ClassAttendanceRecords']["day_22"],
                        'day_23' => $data['ClassAttendanceRecords']["day_23"],
                        'day_24' => $data['ClassAttendanceRecords']["day_24"],
                        'day_25' => $data['ClassAttendanceRecords']["day_25"],
                        'day_26' => $data['ClassAttendanceRecords']["day_26"],
                        'day_27' => $data['ClassAttendanceRecords']["day_27"],
                        'day_28' => $data['ClassAttendanceRecords']["day_28"],
                        'day_29' => $data['ClassAttendanceRecords']["day_29"],
                        'day_30' => $data['ClassAttendanceRecords']["day_30"],
                        'day_31' => isset($data['ClassAttendanceRecords']["day_31"]) ? $data['ClassAttendanceRecords']["day_31"] : 0,
                        ]);
                        }catch (PDOException $e) {
                            
                        }
                }

                if(!empty($data['StudentAbsences'] && isset($data['StudentAbsences']))){
                    try{
                        $statement = $connection->prepare('INSERT INTO institution_student_absences (id, 
                        student_id,
                        institution_id,
                        academic_period_id,
                        institution_class_id,
                        date,
                        absence_type_id,
                        institution_student_absence_day_id,
                        modified_user_id,
                        modified,
                        created_user_id,
                        created)
                        
                        VALUES (:id, 
                        :student_id,
                        :institution_id,
                        :academic_period_id,
                        :institution_class_id,
                        :date,
                        :absence_type_id,
                        :institution_student_absence_day_id,
                        :modified_user_id,
                        :modified,
                        :created_user_id,
                        :created)');

                        $statement->execute([
                        'id' => $data['StudentAbsences']["id"],
                        'student_id' => $data['StudentAbsences']["student_id"],
                        'institution_id' => $data['StudentAbsences']["institution_id"],
                        'academic_period_id' => $data['StudentAbsences']["academic_period_id"],
                        'institution_class_id' => $data['StudentAbsences']["institution_class_id"],
                        'date' => $data['StudentAbsences']["date"],
                        'absence_type_id' => $data['StudentAbsences']["absence_type_id"],
                        'institution_student_absence_day_id' => $data['StudentAbsences']["institution_student_absence_day_id"],
                        'modified_user_id' => $data['StudentAbsences']["modified_user_id"],
                        'modified' => $data['StudentAbsences']["modified"],
                        'created_user_id' => $data['StudentAbsences']["created_user_id"],
                        'created' => $data['StudentAbsences']["created"],
                        ]);
                    }catch (PDOException $e) {
                            
                    }
                }


                if(!empty($data['StudentAbsencesPeriodDetails'] && isset($data['StudentAbsencesPeriodDetails']))){

                    try{
                        $statement = $connection->prepare('INSERT INTO institution_student_absence_details (student_id,
                        institution_id,
                        academic_period_id,
                        institution_class_id,
                        date,
                        period,
                        comment,
                        absence_type_id,
                        student_absence_reason_id,
                        subject_id,
                        modified_user_id,
                        modified,
                        created_user_id,
                        created)
                        
                        VALUES (:student_id,
                        :institution_id,
                        :academic_period_id,
                        :institution_class_id,
                        :date,
                        :period,
                        :comment,
                        :absence_type_id,
                        :student_absence_reason_id,
                        :subject_id,
                        :modified_user_id,
                        :modified,
                        :created_user_id,
                        :created)');

                        $statement->execute([
                        'student_id' => $data['StudentAbsencesPeriodDetails']["student_id"],
                        'institution_id' => $data['StudentAbsencesPeriodDetails']["institution_id"],
                        'academic_period_id' => $data['StudentAbsencesPeriodDetails']["academic_period_id"],
                        'institution_class_id' => $data['StudentAbsencesPeriodDetails']["institution_class_id"],
                        'date' => $data['StudentAbsencesPeriodDetails']["date"],
                        'period' => $data['StudentAbsencesPeriodDetails']["period"],
                        'comment' => $data['StudentAbsencesPeriodDetails']["comment"],
                        'absence_type_id' => $data['StudentAbsencesPeriodDetails']["absence_type_id"],
                        'student_absence_reason_id' => $data['StudentAbsencesPeriodDetails']["student_absence_reason_id"],
                        'subject_id' => $data['StudentAbsencesPeriodDetails']["subject_id"],
                        'modified_user_id' => $data['StudentAbsencesPeriodDetails']["modified_user_id"],
                        'modified' => $data['StudentAbsencesPeriodDetails']["modified"],
                        'created_user_id' => $data['StudentAbsencesPeriodDetails']["created_user_id"],
                        'created' => $data['StudentAbsencesPeriodDetails']["created"],
                        ]);
                    }catch (PDOException $e) {
                            
                    }

                }

                // if(!empty($data['StudentAttendanceMarkedRecords'] && isset($data['StudentAttendanceMarkedRecords']))){

                //     try{
                //         $statement = $connection->prepare('INSERT INTO student_attendance_marked_records (institution_id,
                //         academic_period_id,
                //         institution_class_id,
                //         date,
                //         period,
                //         subject_id)
                        
                //         VALUES (:institution_id,
                //         :academic_period_id,
                //         :institution_class_id,
                //         :date,
                //         :period,
                //         :subject_id)');

                //         $statement->execute([
                //         'institution_id' => $data['StudentAttendanceMarkedRecords']["institution_id"],
                //         'academic_period_id' => $data['StudentAttendanceMarkedRecords']["academic_period_id"],
                //         'institution_class_id' => $data['StudentAttendanceMarkedRecords']["institution_class_id"],
                //         'date' => $data['StudentAttendanceMarkedRecords']["date"],
                //         'period' => $data['StudentAttendanceMarkedRecords']["period"],
                //         'subject_id' => $data['StudentAttendanceMarkedRecords']["subject_id"],
                //         ]);
                //     }catch (PDOException $e) {
                            
                //     }

                // }

                // if(!empty($data['StudentAttendanceMarkTypesTable'] && isset($data['StudentAttendanceMarkTypesTable']))){

                //     try{
                //         $statement = $connection->prepare('INSERT INTO student_attendance_mark_types (id,
                //         name,
                //         code,
                //         education_grade_id,
                //         academic_period_id,
                //         student_attendance_type_id,
                //         attendance_per_day,
                //         modified_user_id,
                //         modified,
                //         created_user_id,
                //         created)
                        
                //         VALUES (:id,
                //         :name,
                //         :code,
                //         :education_grade_id,
                //         :academic_period_id,
                //         :student_attendance_type_id,
                //         :attendance_per_day,
                //         :modified_user_id,
                //         :modified,
                //         :created_user_id,
                //         :created)');

                //         $statement->execute([
                //         'id' => $data['StudentAttendanceMarkTypesTable']["id"],
                //         'name' => $data['StudentAttendanceMarkTypesTable']["name"],
                //         'code' => $data['StudentAttendanceMarkTypesTable']["code"],
                //         'education_grade_id' => $data['StudentAttendanceMarkTypesTable']["education_grade_id"],
                //         'academic_period_id' => $data['StudentAttendanceMarkTypesTable']["academic_period_id"],
                //         'student_attendance_type_id' => $data['StudentAttendanceMarkTypesTable']["student_attendance_type_id"],
                //         'attendance_per_day' => $data['StudentAttendanceMarkTypesTable']["attendance_per_day"],
                //         'modified_user_id' => $data['StudentAttendanceMarkTypesTable']["modified_user_id"],
                //         'modified' => $data['StudentAttendanceMarkTypesTable']["modified"],
                //         'created_user_id' => $data['StudentAttendanceMarkTypesTable']["created_user_id"],
                //         'created' => $data['StudentAttendanceMarkTypesTable']["created"],
                //         ]);
                //     }catch (PDOException $e) {
                            
                //     }

                // }
            }

            /** Deleting all academic period associated table's data according to the requirement */

            // $AssessmentItemResults->deleteAll(['academic_period_id' => $academicPeriodId]);
            // $ClassAttendanceRecords->deleteAll(['academic_period_id' => $academicPeriodId]);
            // $StudentAbsences->deleteAll(['academic_period_id' => $academicPeriodId]);
            // $InstitutionStudentAbsenceDetails->deleteAll(['academic_period_id' => $academicPeriodId]);
            // $StudentAttendanceMarkedRecords->deleteAll(['academic_period_id' => $academicPeriodId]);
            // $StudentAttendanceMarkType->deleteAll(['academic_period_id' => $academicPeriodId]);
        }

        // $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        
        // $AcademicPeriodsData = $AcademicPeriods->find()
        //     ->where(['current'=> 1])
        //     ->first();  
       
        // if($entity['academic_period_id'] == $AcademicPeriodsData->id){
        //     $this->Alert->error('Archive.currentAcademic');
        // }else{
        //     $entity->academic_period_id = $entity['academic_period_id'];
        //     $entity->generated_on = date("Y-m-d H:i:s");
        //     $entity->generated_by = $this->Session->read('Auth.User.id');
        // }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $data){

        /*flag the academic period table
            academic_periods.editable = 0, academic_periods.visible = 0 only when it is not current year-- only update columns*/
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $AcademicPeriods->updateAll(
            ['editable' => 0, 'visible' => 0],    //field
            ['id' => $entity->academic_period_id, 'current'=> 0] //condition
        );

        $this->log('=======>Before triggerDatabaseTransferShell', 'debug');
        $this->triggerDatabaseTransferShell('DatabaseTransfer',$entity->academic_period_id);
        $this->log(' <<<<<<<<<<======== After triggerDatabaseTransferShell', 'debug');

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
    
}
