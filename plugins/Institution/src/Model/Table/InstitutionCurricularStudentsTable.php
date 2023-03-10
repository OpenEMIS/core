<?php
namespace Institution\Model\Table;

use ArrayObject;
use stdClass;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Routing\Router;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Datasource\ResultSetInterface;
use Cake\Network\Session;
use Cake\I18n\Time;
use Cake\Datasource\EntityInterface;

//POCOR-6673
class InstitutionCurricularStudentsTable extends ControllerActionTable
{	
	use MessagesTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('InstitutionCurriculars', ['className' => 'Institution.InstitutionCurriculars']);
        $this->belongsTo('CurricularPositions', ['className' => 'FieldOption.CurricularPositions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);

        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);
        $this->addBehavior('Excel', ['pages' => ['index','view','edit']]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $this->request->query;
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $session = $this->controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $curricularIdGet = $_SESSION['curricularId'];
        $curriculars = TableRegistry::get('institution_curriculars');
        $getAcademicPeriodId = $curriculars->find()
                            ->where([$curriculars->aliasField('id') => $curricularIdGet])
                            ->first()->academic_period_id;
        $selectedAcademicPeriodId = $getAcademicPeriodId;
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $extra['selectedEducationGradeId'] = $selectedEducationGradeId;
        if (!empty($selectedAcademicPeriodId)) {
            $this->request->query['academic_period_id'] = $selectedAcademicPeriodId;
            $gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptionsForIndex($institutionId, $selectedAcademicPeriodId);
            $gradeOptions = [-1 => __('All Grades')] + $gradeOptions;
            $selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
            $this->request->query['education_grade_id'] = $selectedEducationGradeId;
        }
        $extra['elements']['control'] = [
            'name' => 'Institution.Classes/controls',
            'data' => [
              //  'academicPeriodOptions'=>$academicPeriodOptions,
                //'selectedAcademicPeriod'=>$selectedAcademicPeriodId,
                'gradeOptions'=>$gradeOptions,
                'selectedGrade'=>$selectedEducationGradeId,
            ],
            'options' => [],
            'order' => 3
        ];
        if ($this->action == 'index') {
            $tabElements = $this->controller->getCurricularsTabElements();
            $this->controller->set('tabElements', $tabElements);
            $this->controller->set('selectedAction', 'InstitutionCurricularStudents');
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $curricularIdGet = $_SESSION['curricularId'];
        $gradeId  = $this->request->query['education_grade_id'];
        $session = $this->controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $institutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $grades = TableRegistry::get('education_grades');
        $institutionClass = TableRegistry::get('institution_classes');
        $curricularPositions = TableRegistry::get('curricular_positions');
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $institutionClassGrades = TableRegistry::get('institution_class_grades');
        $Users = TableRegistry::get('security_users');
        $curricularIdGet = $_SESSION['curricularId'];
        $conditions = [];
        $conditions[$this->aliasField('institution_curricular_id')]  = $curricularIdGet;
        if($gradeId != -1){
            $conditions[$grades->aliasField('id')]  = $gradeId;
        }

        $conditions[$institutionStudents->aliasField('institution_id')]  = $institutionId;
        $conditions[$InstitutionCurriculars->aliasField('institution_id')]  = $institutionId;
        $query
            ->select([
                        $this->aliasField('id'),
                        $this->aliasField('student_id'),
                        'openemis_no'=>  $Users->aliasField('openemis_no'),
                        'education_grade' => $grades->aliasField('name'),
                        'institution_class' => $institutionClass->aliasField('name'),
                        'curricular_position' => $curricularPositions->aliasField('name'),     
                        $InstitutionCurriculars->aliasField('name') ,      
                        $InstitutionCurriculars->aliasField('id')       
                ])
                ->LeftJoin([$institutionStudents->alias() => $institutionStudents->table()],
                    [$institutionStudents->aliasField('student_id').' = ' . $this->aliasField('student_id')
                ])
                ->LeftJoin([$Users->alias() => $Users->table()],
                    [$Users->aliasField('id').' = ' . $this->aliasField('student_id')
                ])
                ->LeftJoin([$grades->alias() => $grades->table()],
                    [$grades->aliasField('id').' = ' . $institutionStudents->aliasField('education_grade_id')
                ])
                ->LeftJoin([$institutionClassGrades->alias() => $institutionClassGrades->table()],
                    [$institutionClassGrades->aliasField('education_grade_id').' = ' . $institutionStudents->aliasField('education_grade_id')
                ])
                ->LeftJoin([$institutionClass->alias() => $institutionClass->table()],
                    [$institutionClass->aliasField('id').' = ' . $institutionClassGrades->aliasField('institution_class_id')
                ])
                ->LeftJoin([$curricularPositions->alias() => $curricularPositions->table()],
                    [$curricularPositions->aliasField('id').' = ' . $this->aliasField('curricular_position_id')
                ])
                ->LeftJoin([$InstitutionCurriculars->alias() => $InstitutionCurriculars->table()],
                    [$InstitutionCurriculars->aliasField('id').' = ' . $this->aliasField('institution_curricular_id')
                ])->where($conditions)->group([$this->aliasField('student_id')]);

        if (!$sortable) {
            $query
                ->order([
                    $this->aliasField('student_id') => 'ASC'
                ]);
        }
        $extra['order'] = [$this->aliasField('name') => 'asc'];

        $search = $this->getSearchKey();

        // CUSTOM SEACH 
        $extra['auto_search'] = false; // it will append an AND
        if (!empty($search)) {
            $query->find('byStudentData', ['search' => $search]);
        }

        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        $query = $this->request->query;
        $this->field('student_id', ['visible' => false]);
        $this->field('institution_curricular_id', ['visible' => true]);
        $this->field('curricular_position_id', ['visible' => true]);
        $this->field('start_date', ['visible' => false]);
        $this->field('end_date', ['visible' => false]);
        $this->field('hours', ['visible' => false]);
        $this->field('points', ['visible' => false]);
        $this->field('location', ['visible' => false]);
        $this->field('comments', ['visible' => false]);
        $this->field('openemis_no', ['visible' => ['index'=>true,'view' => true]]);
        $this->field('education_grade', ['visible' => true]);
        $this->field('institution_class', ['visible' => true]);
        $this->field('student_name', ['visible' => true]);
        
        $this->setFieldOrder([
        'student_name', 'openemis_no','education_grade','institution_class', 'institution_curricular_id', 'curricular_position_id']);
               
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $curricularIdGet = $_SESSION['curricularId'];
        $curriculars = TableRegistry::get('institution_curriculars');
        $academicPeriod = TableRegistry::get('academic_periods');
        $curricularType = TableRegistry::get('curricular_types');
        $curricularData = $curriculars->find()
                            ->select(['name'=>$curriculars->aliasField('name'),'category'=>$curriculars->aliasField('category'),
                                'academic_period'=>$academicPeriod->aliasField('name'),
                                'curricularType'=>$curricularType->aliasField('name')
                                        ])
                            ->LeftJoin([$academicPeriod->alias() => $academicPeriod->table()],[
                                $academicPeriod->aliasField('id').' = ' . $curriculars->aliasField('academic_period_id')
                            ])
                            ->LeftJoin([$curricularType->alias() => $curricularType->table()],[
                                $curricularType->aliasField('id').' = ' . $curriculars->aliasField('curricular_type_id')
                            ])
                            ->where([$curriculars->aliasField('id') => $curricularIdGet])->first();
        
        $entity->name = $curricularData->name;
        $entity->category = $curricularData->category ? __('Curricular') : __('Extracurricular');
        $entity->academic_period = $curricularData->academic_period;
        $entity->curricularType = $curricularData->curricularType;
        $this->field('academic_period_id', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $entity->academic_period, 'required' => true]]);
        $this->field('name', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $entity->name, 'required' => true]]);
        $this->field('curricular_type_id', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $entity->curricularType, 'required' => true]]);
        $this->field('category', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $entity->category, 'required' => true]]);
        $this->field('student_id', ['type' => 'select','visible' => true]);
        $this->field('institution_curricular_id', ['visible' => false]);
        $this->field('start_date',['attr' => ['label' => __('Start Date')]]);
        $this->field('end_date',['attr' => ['label' => __('End Date')]]);
        $this->field('hours', ['visible' => true,]);
        $this->field('points', ['visible' => true,]);
        $this->field('location', ['visible' => true,]);
        $this->field('curricular_position_id', ['type' => 'select']);
        $this->field('comments', ['visible' => true]);
        $this->field('id', ['visible' => true]);

    }

   public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            return $this->updateDateRangeField('start_date', $attr, $request);
        }
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            return $this->updateDateRangeField('end_date', $attr, $request);
        }
    }

    // Misc
    private function updateDateRangeField($key, $attr, Request $request)
    {
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $requestData = $request->data;
        if (array_key_exists($this->alias(), $requestData) && array_key_exists('academic_period_id', $requestData[$this->alias()])) {
            $selectedPeriodId = $requestData[$this->alias()]['academic_period_id'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }

        $selectedPeriod = $this->AcademicPeriods->get($selectedPeriodId);
        $attr['type'] = 'date';
        $attr['date_options']['startDate'] = $selectedPeriod->start_date->format('d-m-Y');
        $attr['date_options']['endDate'] = $selectedPeriod->end_date->format('d-m-Y');
        
        if (!array_key_exists($this->alias(), $requestData) || !array_key_exists($key, $requestData[$this->alias()])) {
            if ($selectedPeriodId != $this->AcademicPeriods->getCurrent()) {
                $attr['value'] = $selectedPeriod->start_date;
            } else {
                $attr['value'] = Time::now();
            }
        }

        return $attr;
    }

    public function onUpdateFieldCurricularPositionId(Event $event, array $attr, $action, Request $request)
    {
        $curricularPositions = TableRegistry::get('curricular_positions');
        $curricularPositionsList = $curricularPositions->find('list')->where(['visible'=>1])->toArray();
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Position') . ' --'] + $curricularPositionsList;
            $attr['onChangeReload'] = false;  
        }
        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request)
    {
        $institutionStudents = TableRegistry::get('institution_students');
        $securityUsers = TableRegistry::get('security_users');
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $curricularIdGet = $_SESSION['curricularId'];
        $academicPeriodId = $InstitutionCurriculars->find()->where(['id'=>$curricularIdGet])->first()->academic_period_id;
        $session = $this->controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $studentData = $institutionStudents->find('all')->select
                        ([
                            'openemis_no' => $securityUsers->aliasField('openemis_no'),
                            'id' => $securityUsers->aliasField('id'),
                            'first_name' => $securityUsers->aliasField('first_name'),
                            'last_name' => $securityUsers->aliasField('last_name'),
                        ])
                        ->LeftJoin([$securityUsers->alias() => $securityUsers->table()],[
                            $securityUsers->aliasField('id').' = ' . $institutionStudents->aliasField('student_id')
                        ])
                        ->where(['student_status_id'=>1,'institution_id'=>$institutionId,'academic_period_id'=>$academicPeriodId])->toArray();
        $studentList = [] ;
        foreach($studentData as $student){
                $studentList[$student->id] = $student->openemis_no.' - '.$student->first_name.' '.$student->last_name;
        }
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Student') . ' --'] + $studentList;
            $attr['onChangeReload'] = false;  
        }
        return $attr;
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $options) 
    {
        
        $entity->id = Text::uuid();
        $entity->institution_curricular_id = $_SESSION['curricularId'];
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $options) 
    {
        $entity->institution_curricular_id = $_SESSION['curricularId'];
        $entity->id = $entity->id;

    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['visible' => true]);
        $this->field('openemis_no', ['visible' => true]);
        $this->field('category', ['visible' => true]);
        $this->field('curricular_type_id', ['visible' => true]);
    }

    public function onGetCategory(Event $event, Entity $entity)
    {
        return $entity->category ? __('Curricular') : __('Extracurricular');
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {

        return $entity->user->openemis_no;
    }

    public function onGetCurricularTypeId(Event $event, Entity $entity)
    {
        $curricularsID =  $entity->institution_curricular_id;
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $curricular_type = TableRegistry::get('curricular_types');
        $data = $InstitutionCurriculars->find()
                ->select(['name' => $curricular_type->aliasField('name')])
                ->LeftJoin([$curricular_type->alias() => $curricular_type->table()],
                    [$curricular_type->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
                ])
                ->where([$InstitutionCurriculars->aliasField('id') => $curricularsID])
                ->first();
        return $data->name;

    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        $curricularsID =  $entity->institution_curricular_id;
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $academicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $data = $InstitutionCurriculars->find()
                ->select(['name' => $academicPeriod->aliasField('name')])
                ->LeftJoin([$academicPeriod->alias() => $academicPeriod->table()],
                    [$academicPeriod->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('academic_period_id')
                ])
                ->where([$InstitutionCurriculars->aliasField('id') => $curricularsID])
                ->first();
        return $data->name;

    }

    public function onGetStudentName(Event $event, Entity $entity)
    {
        $StudentId = $entity->student_id;
        $users = TableRegistry::get('security_users'); 
        $data = $this->find()->select(['first_name'=>$users->aliasField('first_name'),'middle_name'=>$users->aliasField('middle_name'),'third_name'=>$users->aliasField('third_name'),'last_name'=>$users->aliasField('last_name')])
                ->leftJoin([$users->alias() => $users->table()],
                    [$users->aliasField('id').' = ' . $this->aliasField('student_id')
                ])
                ->where([$this->aliasField('student_id') => $StudentId ])->first();
        $student = $data->first_name.' '.$data->middle_name.' '.$data->third_name.' '.$data->last_name;
        
        return $student;
    }

    public function findByStudentData(Query $query, array $options)
    {
        if (array_key_exists('search', $options)) {
            $search = $options['search'];
            $query
            ->join([
                [
                    'table' => 'security_users', 'alias' => 'SecurityUsers', 'type' => 'INNER',
                    'conditions' => ['SecurityUsers.id = ' . $this->aliasField('student_id')]
                ],
                [
                    'table' => 'institution_students', 'alias' => 'InstitutionStudents', 'type' => 'INNER',
                    'conditions' => ['InstitutionStudents.student_id = ' . $this->aliasField('student_id')]
                ],
                [
                    'table' => 'education_grades', 'alias' => 'EducationGrades', 'type' => 'LEFT',
                    'conditions' => [
                        'EducationGrades.id = ' . 'InstitutionStudents.education_grade_id',
                    ]
                ],
                [
                    'table' => 'institution_curriculars', 'alias' => 'InstitutionCurriculars', 'type' => 'INNER',
                    'conditions' => ['InstitutionCurriculars.id = ' . $this->aliasField('institution_curricular_id')]
                ],
                [
                    'table' => 'curricular_positions', 'alias' => 'CurricularPositions', 'type' => 'INNER',
                    'conditions' => ['CurricularPositions.id = ' . $this->aliasField('curricular_position_id')]
                ],
            ])
            ->where([
                    'OR' => [
                        ['SecurityUsers.openemis_no LIKE' => '%' . $search . '%'],
                        ['SecurityUsers.first_name LIKE' => '%' . $search . '%'],
                        ['SecurityUsers.middle_name LIKE' => '%' . $search . '%'],
                        ['SecurityUsers.third_name LIKE' => '%' . $search . '%'],
                        ['SecurityUsers.last_name LIKE' => '%' . $search . '%'],
                        ['InstitutionCurriculars.name LIKE' => '%' . $search . '%'],
                        ['CurricularPositions.name LIKE' => '%' . $search . '%'],
                        ['EducationGrades.name LIKE' => '%' . $search . '%'],
                        
                    ]
                ]
            )
            ->group($this->aliasField('student_id'))
            ;
        }

        return $query;
    }

    public function beforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $curricularStudent = TableRegistry::get('institution_curricular_students'); 
        $checkStudent =  $curricularStudent->find()->where([$curricularStudent->aliasField('student_id')=>$entity->student_id])->first();     
             
        if(!empty($checkStudent)){
            $message = __('Its Associated with Other Data');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $curricularStudent = TableRegistry::get('institution_curricular_students');
        $users = TableRegistry::get('security_users');
        $countMaleFemale = $curricularStudent->find()
                     ->select(['male_students' => "
                                 (COUNT(DISTINCT(CASE WHEN security_users.gender_id = 1 THEN institution_curricular_students.student_id END))) ",
                                'female_students' => "
                                 (COUNT(DISTINCT(CASE WHEN security_users.gender_id = 2 THEN institution_curricular_students.student_id END))) "

                                ])
                    ->InnerJoin([$users->alias() => $users->table()],
                    [$users->aliasField('id').' = ' . $curricularStudent->aliasField('student_id')])
                    ->where([$curricularStudent->aliasField('institution_curricular_id') => $entity->institution_curricular_id])
                    ->group([$curricularStudent->aliasField('student_id')]);
        foreach($countMaleFemale as $value){
            $maleStudents  = $value->male_students;
            $femaleStudents  = $value->female_students;
        }
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $updateCurricular =   $InstitutionCurriculars->updateAll(
                                ['total_male_students' => $maleStudents,'total_female_students'=>$femaleStudents],    //field
                                [
                                 'id' => $entity->institution_curricular_id, 
                                ]);

    }

	
}
