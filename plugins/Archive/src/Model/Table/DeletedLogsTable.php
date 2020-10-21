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

        $this->belongsTo('Users', [
            'className' => 'User.Users', 
            'foreignKey' => 'generated_by'
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
        //$validator->integer('academic_period_id')->requirePresence('academic_period_id', 'create')->notEmpty('academic_period_id');

        /*$validator->dateTime('generated_on')->requirePresence('generated_on', 'create')->notEmpty('generated_on');*/
        //$validator->string('generated_by', 'create')->notEmpty('generated_by');
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
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data){

        //$entity->errors('academic_period_id', __('Please remember to backup first before you proceed to delete this data.'));
        $this->Alert->error('Please remember to backup first before you proceed to delete this data',['reset' => true]);

        //echo '<pre>'; print_r($entity); die; //$data['academic_period_id']; die;
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
        // $AcademicPeriods->updateAll(
        //     ['editable' => 0, 'visible' => 0],    //field
        //     ['id' => $entity->academic_period_id] //condition
        // );

        /*delete the rows of mentioned tables according to academic_period_id
        deleting the rows of --
        assessment_item_results
        student_attendance_marked_records
        institution_student_absence_details
        institution_student_absences*/

        // $AssessmentItemResults->deleteAll([
        //     'academic_period_id'=>$entity->academic_period_id
        // ]);

        // $StudentAttendanceMarkedRecords->deleteAll([
        //     'academic_period_id'=>$entity->academic_period_id
        // ]);

        // $InstitutionStudentAbsenceDetails->deleteAll([
        //     'academic_period_id'=>$entity->academic_period_id
        // ]);

        // $InstitutionStudentAbsences->deleteAll([
        //     'academic_period_id'=>$entity->academic_period_id
        // ]);
        
    }
    
}
