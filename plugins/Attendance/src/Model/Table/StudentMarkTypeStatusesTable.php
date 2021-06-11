<?php
namespace Attendance\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\ResultSet;

class StudentMarkTypeStatusesTable extends ControllerActionTable
{
    private $defaultMarkType;
    private $_contain = ['EducationGrades'];


    public function initialize(array $config)
    {
        parent::initialize($config);

         $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->hasMany('StudentMarkTypeStatusGrades', ['className' => 'Attendance.StudentMarkTypeStatusGrades', 'foreignKey' => 'student_mark_type_status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'joinTable' => 'student_mark_type_status_grades',
            'foreignKey' => 'student_mark_type_status_id',
            'targetForeignKey' => 'education_grade_id'
        ]);
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('date_enabled', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'message' => __('Date enabled should be in academic period')
                ],
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'date_disabled', false]
                ]
            ])
            ->add('date_disabled', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                'message' => __('Date disabled should be in academic period')
            ]);

        return $validator;
    } 

    public function beforeAction(Event $event)
    {

        $this->field('academic_period_id', ['type' => 'select']);       
        $this->field('student_attendance_mark_type_id', ['type' => 'select']);   
        $this->field('education_grades', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select EducationGrades'),
            'visible' => true
        ]); 

        if ($this->action == 'index' || $this->action == 'view') {
        $this->field('student_attendance_mark_type_id', ['attr' => ['label' => __('Name')]]);
        $this->setFieldOrder([
            'student_attendance_mark_type_id', 'date_enabled', 'date_disabled', 'academic_period_id', 'education_grades'
        ]);
       } else {
        $this->field('student_attendance_mark_type_id', ['attr' => ['label' => __('Attendance Type')]]);
        $this->setFieldOrder([
            'academic_period_id', 'student_attendance_mark_type_id', 'date_enabled', 'date_disabled', 'education_grades'
        ]);
       }
    }

    public function indexBeforeQuery(Event $event, Query $query)
    {
        $query->contain($this->_contain);
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain($this->_contain);
    }

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
	{   
		if(!empty($entity->education_grades) && !empty($entity->academic_period_id) && !empty($entity->student_attendance_mark_type_id) && !empty($entity->date_enabled)) {
			$educationGrades = [];
			foreach($entity->education_grades as $educationGrade) {
				$educationGrades[] = $educationGrade->id;	
			}
			
			$existingStatusCount = $this->find()
			->select([$this->aliasField('id')])
			->innerJoinWith('StudentMarkTypeStatusGrades')
			->where([
				$this->aliasField('academic_period_id') => $entity->academic_period_id,
				$this->aliasField('student_attendance_mark_type_id') => $entity->student_attendance_mark_type_id,
				'StudentMarkTypeStatusGrades.education_grade_id IN' => $educationGrades,
				$this->aliasField('date_disabled >=') => $entity->date_enabled,
			])
			->count();

			if ($existingStatusCount) {
				$this->Alert->warning($this->aliasField('statusAlreadyAdded'));
				$event->stopPropagation();
				return $this->controller->redirect($this->url('index'));
			} else {
				return $process;
			}	
		}
	}
	
	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$academicPeriodId = !is_null($entity->academic_period_id) ? $entity->academic_period_id : $AcademicPeriod->getCurrent();	
        list($educationGradeOptions) = array_values($this->getSelectOptions($academicPeriodId));
        $this->fields['education_grades']['options'] = $educationGradeOptions;
    }
	
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodOptions = $AcademicPeriods->getYearList();
				
        $attr['type'] = 'select';

        $attr['placeholder'] = __('Select Academic Periods');
        $attr['attr']['options'] = $periodOptions;
		$attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldStudentAttendanceMarkTypeId(Event $event, array $attr, $action, Request $request)
    {
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $studentAttendanceMarkTypesOptions = $StudentAttendanceMarkTypes
                                                ->find( 'list', 
                                                ['keyField' => 'id',
                                                'valueField' => 'name'])
                                                ->all()
                                                ->toArray();
        $attr['type'] = 'select';

        $attr['placeholder'] = __('Select Mark Type');
        $attr['options'] = $studentAttendanceMarkTypesOptions;
        
        return $attr;
    }

    public function getSelectOptions($academicPeriodId)
    {
        //Return all required options and their key      

        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $educationGradeOptions = $EducationGrades
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->find('visible')
            ->find('order')
			->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
            ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
			->toArray();
        $selectedEducationGrade = key($educationGradeOptions);
        return compact('educationGradeOptions', 'selectedEducationGrade');
    }
}
