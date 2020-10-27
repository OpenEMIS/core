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
 */class DeletedLogsTable extends ControllerActionTable
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('deleted_logs');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->belongsTo('AcademicPeriods', [
            'foreignKey' => 'academic_period_id',
            'joinType' => 'INNER',
            'className' => 'AcademicPeriod.AcademicPeriods'
        ]);

        /*$this->belongsTo('Users', [
            'className' => 'User.Users', 
            'foreignKey' => 'generated_by'
        ]);*/

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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id');    
        $this->field('generated_on');
        $this->field('generated_by');

        $this->setFieldOrder(['academic_period_id','generated_on','generated_by']);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('id', ['visible' => false]);
        $this->field('generated_on', ['visible' => false]);
        $this->field('generated_by', ['visible' => false]);
        
        $this->setFieldOrder(['academic_period_id']);

        $this->Alert->warning('Archive.backupReminder', ['reset' => true]);
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

        $entity->academic_period_id = $entity['academic_period_id'];
        $entity->generated_on = date("Y-m-d H:i:s");
        $entity->generated_by = $this->Session->read('Auth.User.id');
        
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $data){

        //loading all tables to update and delete rows from other tables
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $InstitutionStudentAbsenceDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        $InstitutionStudentAbsences = TableRegistry::get('Institution.StudentAttendances');
        
        /*flag the academic period table
        academic_periods.editable = 0, academic_periods.visible = 0 -- only update columns*/
        $AcademicPeriods->updateAll(
            ['editable' => 0, 'visible' => 0],    //field
            ['id' => $entity->academic_period_id] //condition
        );

        //get archive database connection
        $connection = ConnectionManager::get('prd_cor_arc');

        /** Select assessment item result records as per academic period and save it in different database and then delete them */
        $AssessmentItemResultsData = $AssessmentItemResults->find()
            ->where(['academic_period_id' => $entity->academic_period_id])
            ->all();  
        //echo '<pre>';
        if(!empty($AssessmentItemResultsData) && isset($AssessmentItemResultsData)){
            foreach($AssessmentItemResultsData as $items){

                $connection->execute('INSERT INTO assessment_item_results (id,marks, assessment_grading_option_id, student_id, assessment_id, education_subject_id, education_grade_id, academic_period_id, assessment_period_id, institution_id, modified_user_id, modified, created_user_id, created) VALUES ("'.$items["id"].'","'.$items["marks"].'","'.$items["assessment_grading_option_id"].'","'.$items["student_id"].'","'.$items["assessment_id"].'","'.$items["education_subject_id"].'","'.$items["education_grade_id"].'","'.$items["academic_period_id"].'","'.$items["assessment_period_id"].'","'.$items["institution_id"].'","'.$items["modified_user_id"].'","'.$items["modified"].'","'.$items["created_user_id"].'","'.$items["created"].'")');

                exit;
            }
        }
        /*$AssessmentItemResults->deleteAll([
            'academic_period_id'=>$entity->academic_period_id
        ]);*/

        /** Select student_attendance_marked_records as per academic period and save it in different database and then delete them */
        $StudentAttendanceMarkedRecordsData = $StudentAttendanceMarkedRecords->find()
            ->where(['academic_period_id' => $entity->academic_period_id])
            ->all();  
        //echo '<pre>';
        if(!empty($StudentAttendanceMarkedRecordsData) && isset($StudentAttendanceMarkedRecordsData)){
            foreach($StudentAttendanceMarkedRecordsData as $attendance){
                //print($attendance); die;

                $connection->execute('INSERT INTO student_attendance_marked_records (institution_id,academic_period_id, institution_class_id, date, period, subject_id) VALUES ("'.$attendance["institution_id"].'","'.$attendance["academic_period_id"].'","'.$attendance["institution_class_id"].'","'.$attendance["date"].'","'.$attendance["period"].'","'.$attendance["subject_id"].'")');

                exit;
            }
        }
        // $StudentAttendanceMarkedRecords->deleteAll([
        //     'academic_period_id'=>$entity->academic_period_id
        // ]);

        /** Select institution_student_absence_details records as per academic period and save it in different database and then delete them */
        $InstitutionStudentAbsenceDetailsData = $InstitutionStudentAbsenceDetails->find()
            ->where(['academic_period_id' => $entity->academic_period_id])
            ->all();  
        //echo '<pre>';
        if(!empty($InstitutionStudentAbsenceDetailsData) && isset($InstitutionStudentAbsenceDetailsData)){
            foreach($InstitutionStudentAbsenceDetailsData as $absence){
                //print($absence); die;

                $connection->execute('INSERT INTO institution_student_absence_details (student_id,institution_id, academic_period_id, institution_class_id,date, period, comment,absence_type_id,student_absence_reason_id,subject_id,modified_user_id,modified,created_user_id,created) VALUES ("'.$attendaabsencence["student_id"].'","'.$absence["institution_id"].'","'.$absence["academic_period_id"].'","'.$absence["institution_class_id"].'","'.$absence["date"].'","'.$absence["period"].'","'.$absence["comment"].'","'.$absence["absence_type_id"].'","'.$absence["student_absence_reason_id"].'","'.$absence["subject_id"].'","'.$absence["modified_user_id"].'","'.$absence["modified"].'","'.$absence["created_user_id"].'","'.$absence["created"].'")');

                exit;
            }
        }
        // $InstitutionStudentAbsenceDetails->deleteAll([
        //     'academic_period_id'=>$entity->academic_period_id
        // ]);

        /** Select institution_student_absence_details records as per academic period and save it in different database and then delete them */
        $InstitutionStudentAbsencesData = $InstitutionStudentAbsences->find()
            ->where(['academic_period_id' => $entity->academic_period_id])
            ->all();  
        //echo '<pre>';
        if(!empty($InstitutionStudentAbsencesData) && isset($InstitutionStudentAbsencesData)){
            foreach($InstitutionStudentAbsencesData as $absences){
                //print($absences); die;

                $connection->execute('INSERT INTO institution_student_absence_details (id,student_id,institution_class_id,education_grade_id,academic_period_id,next_institution_class_id,institution_id,student_status_id,modified_user_id,modified,created_user_id,created ) VALUES ("'.$absences["id"].'","'.$absences["student_id"].'","'.$absences["institution_class_id"].'","'.$absences["education_grade_id"].'","'.$absences["academic_period_id"].'","'.$absences["next_institution_class_id"].'","'.$absences["institution_id"].'","'.$absences["student_status_id"].'","'.$absences["modified_user_id"].'","'.$absences["modified"].'","'.$absences["created_user_id"].'","'.$absences["created"].'")');

                exit;
            }
        }
        // $InstitutionStudentAbsences->deleteAll([
        //     'academic_period_id'=>$entity->academic_period_id
        // ]);
        $url = $this->url('index');
        return $this->controller->redirect($url);
        
    }
    
}
