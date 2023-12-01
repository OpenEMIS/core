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
use Cake\Datasource\ConnectionManager;
use Cake\Http\ServerRequest;

//POCOR-6673
class InstitutionCurricularStudentsTable extends ControllerActionTable
{	
	use MessagesTrait;

    public function initialize(array $config): void
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
        $query = $this->request->getQuery();
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $session = $this->controller->getRequest()->getSession();
        $institutionId = $session->read('Institution.Institutions.id');

        $curricularIdGet = $_SESSION['curricularId'];
        $curriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $getAcademicPeriodId = $curriculars->find()
                            ->where([$curriculars->aliasField('id') => $curricularIdGet])
                            ->first()->academic_period_id;
        $selectedAcademicPeriodId = $getAcademicPeriodId;
        $this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $extra['selectedEducationGradeId'] = $selectedEducationGradeId;
        if (!empty($selectedAcademicPeriodId)) {
            //$this->request->getQuery('academic_period_id') = $selectedAcademicPeriodId;
            $this->request = $this->request->withQueryParams(['academic_period_id' => $selectedAcademicPeriodId]);
            $gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptionsForIndex($institutionId, $selectedAcademicPeriodId);
            $gradeOptions = [-1 => __('All Grades')] + $gradeOptions;
            $selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
            //$this->request->getQuery('education_grade_id') = $selectedEducationGradeId;
            $this->request = $this->request->withQueryParams(['education_grade_id' => $selectedEducationGradeId]);
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
        $gradeId  = $this->request->getQuery('education_grade_id');
        $session = $this->controller->getRequest()->getSession();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $grades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $institutionClass = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $curricularPositions = TableRegistry::getTableLocator()->get('FieldOption.CurricularPositions');
        $InstitutionCurriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $institutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        $academicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $curricular_types = TableRegistry::getTableLocator()->get('FieldOption.CurricularTypes');
        $Users = TableRegistry::getTableLocator()->get('User.Users');
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
                        'start_date'=>$this->aliasField('start_date'),
                        'end_date'=>$this->aliasField('end_date'),
                        'openemis_no'=>  $Users->aliasField('openemis_no'),
                        'education_grade' => $grades->aliasField('name'),
                        'academic_period_id'=>$academicPeriods->aliasField('name'),
                        'institution_class' => $institutionClass->aliasField('name'),
                        'curricular_position' => $curricularPositions->aliasField('name'),     
                        'category'=>$InstitutionCurriculars->aliasField('category'),
                        'type'=>$curricular_types->aliasField('name'),
                        $InstitutionCurriculars->aliasField('name') ,      
                        $InstitutionCurriculars->aliasField('id')       
                ])
                ->LeftJoin([$institutionStudents->getAlias() => $institutionStudents->getTable()],
                    [$institutionStudents->aliasField('student_id').' = ' . $this->aliasField('student_id')
                ])
                ->LeftJoin([$Users->getAlias() => $Users->getTable()],
                    [$Users->aliasField('id').' = ' . $this->aliasField('student_id')
                ])
                ->LeftJoin([$grades->getAlias() => $grades->getTable()],
                    [$grades->aliasField('id').' = ' . $institutionStudents->aliasField('education_grade_id')
                ])
                ->LeftJoin(
                    [$institutionClassGrades->getAlias() => $institutionClassGrades->getTable()],
                    [$institutionClassGrades->aliasField('education_grade_id').' = ' . $institutionStudents->aliasField('education_grade_id')]
                )
                ->LeftJoin([$institutionClass->getAlias() => $institutionClass->getTable()],
                    [$institutionClass->aliasField('id').' = ' . $institutionClassGrades->aliasField('institution_class_id')
                ])
                ->LeftJoin([$curricularPositions->getAlias() => $curricularPositions->getTable()],
                    [$curricularPositions->aliasField('id').' = ' . $this->aliasField('curricular_position_id')
                ])
                ->LeftJoin([$InstitutionCurriculars->getAlias() => $InstitutionCurriculars->getTable()],
                    [$InstitutionCurriculars->aliasField('id').' = ' . $this->aliasField('institution_curricular_id')
                ])
                ->LeftJoin([$academicPeriods->getAlias() => $academicPeriods->getTable()],
                [$academicPeriods->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('academic_period_id')
                ])
                ->LeftJoin([$curricular_types->getAlias() => $curricular_types->getTable()],
                [$curricular_types->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
            ])
                ->where($conditions)->group([$this->aliasField('student_id')]);

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
        echo "<pre>"; print_r($this->controllerAction);
die;
        $query = $this->request->getQuery();
        $this->field('academic_period_id', ['visible' => true]);
        $this->field('student_id', ['visible' => false]);
        $this->field('student_name', ['visible' => true]);
        $this->field('curricular_category', ['visible' => true]);
        $this->field('curricular_position_id', ['visible' => true]);
        $this->field('type', ['visible' => ['index'=>true,'view' => true,'edit' => false,'add'=>false]]);
        $this->field('start_date', ['visible' => true]);
        $this->field('end_date', ['visible' => true]);
        $this->field('education_grade', ['visible' => true]);
        $this->field('institution_class', ['visible' => true]);
        $this->field('institution_curricular_id', ['visible' => true]);
        
        $this->field('hours', ['visible' => false]);
        $this->field('points', ['visible' => false]);
        $this->field('location', ['visible' => false]);
        $this->field('comments', ['visible' => false]);
        $this->field('openemis_no', ['visible' => ['index'=>true,'view' => false]]);
        
        $this->setFieldOrder([
        'academic_period_id','student_name','openemis_no','education_grade','institution_class','curricular_category','type', 'institution_curricular_id', 'curricular_position_id','start_date','end_date']); //POCOR-7604
               
    }

    
    public function onGetEducationGrade(Event $event, Entity $entity)
    {    
        $sId = $entity->student_id;
        $ins_id = $entity->institution_curricular->institution_id;
        $academic_period_id = $entity->institution_curricular->academic_period_id;
        $connection = ConnectionManager::get('default');
        $ins_class_rec = $connection->query("SELECT education_grades.name,education_programmes.name FROM institution_class_students LEFT JOIN education_grades ON education_grades.id=institution_class_students.education_grade_id LEFT JOIN education_programmes ON education_programmes.id=education_grades.education_programme_id WHERE institution_class_students.student_id=".$sId.' AND institution_class_students.academic_period_id='.$academic_period_id.' AND institution_class_students.institution_id='.$ins_id.' Order by institution_class_students.id desc limit 1');
        $ins_class_data = $ins_class_rec->fetch();
        return (!empty( $ins_class_data)) ?  $ins_class_data[1] .' - '.$ins_class_data[0] : '--';
    }

    public function onGetInstitutionClass(Event $event, Entity $entity)
    {    
        $sId = $entity->student_id;
        $ins_id = $entity->institution_curricular->institution_id;
        $academic_period_id = $entity->institution_curricular->academic_period_id;
        $connection = ConnectionManager::get('default');
        $ins_class_rec = $connection->query("SELECT institution_classes.name FROM institution_class_students LEFT JOIN institution_classes ON institution_classes.id=institution_class_students.institution_class_id  WHERE institution_class_students.student_id=".$sId.' AND institution_class_students.academic_period_id='.$academic_period_id.' AND institution_class_students.institution_id='.$ins_id.' Order by institution_class_students.id desc limit 1');
        $ins_class_data = $ins_class_rec->fetch();
        return (!empty( $ins_class_data)) ?  $ins_class_data[0] : '--';
    }

    public function onGetCurricularCategory(Event $event, Entity $entity)
    {
        return $entity['institution_curricular']['category'] ? __('Curricular') : $entity->category ? __('Curricular') : __('Extracurricular');    
        
    }
    
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $curricularIdGet = $_SESSION['curricularId'];
        $curriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $academicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $curricularType = TableRegistry::getTableLocator()->get('FieldOption.CurricularTypes');
        $curricularData = $curriculars->find()
                            ->select(['name'=>$curriculars->aliasField('name'),'category'=>$curriculars->aliasField('category'),
                                'academic_period'=>$academicPeriod->aliasField('name'),
                                'curricularType'=>$curricularType->aliasField('name')
                                        ])
                            ->LeftJoin([$academicPeriod->getAlias() => $academicPeriod->getTable()],[
                                $academicPeriod->aliasField('id').' = ' . $curriculars->aliasField('academic_period_id')
                            ])
                            ->LeftJoin([$curricularType->getAlias() => $curricularType->getTable()],[
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

   public function onUpdateFieldStartDate(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            return $this->updateDateRangeField('start_date', $attr, $request);
        }
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            return $this->updateDateRangeField('end_date', $attr, $request);
        }
    }

    // Misc
    private function updateDateRangeField($key, $attr, ServerRequest $request)
    {
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $requestData = $request->getData();
        if (array_key_exists($this->getAlias(), $requestData) && array_key_exists('academic_period_id', $requestData[$this->getAlias()])) {
            $selectedPeriodId = $requestData[$this->getAlias()]['academic_period_id'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }

        $selectedPeriod = $this->AcademicPeriods->get($selectedPeriodId);
        $attr['type'] = 'date';
        $attr['date_options']['startDate'] = $selectedPeriod->start_date->format('d-m-Y');
        $attr['date_options']['endDate'] = $selectedPeriod->end_date->format('d-m-Y');
        
        if (!array_key_exists($this->getAlias(), $requestData) || !array_key_exists($key, $requestData[$this->getAlias()])) {
            if ($selectedPeriodId != $this->AcademicPeriods->getCurrent()) {
                $attr['value'] = $selectedPeriod->start_date;
            } else {
                $attr['value'] = Time::now();
            }
        }

        return $attr;
    }

    public function onUpdateFieldCurricularPositionId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $curricularPositions = TableRegistry::getTableLocator()->get('FieldOption.CurricularPositions');
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

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $securityUsers = TableRegistry::getTableLocator()->get('User.Users');
        $InstitutionCurriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $curricularIdGet = $_SESSION['curricularId'];
        $academicPeriodId = $InstitutionCurriculars->find()->where(['id'=>$curricularIdGet])->first()->academic_period_id;
        $session = $this->controller->getRequest()->getSession();
       
        $institutionId = $session->read('Institution.Institutions.id');
        $studentData = $institutionStudents->find('all')->select
                        ([
                            'openemis_no' => $securityUsers->aliasField('openemis_no'),
                            'id' => $securityUsers->aliasField('id'),
                            'first_name' => $securityUsers->aliasField('first_name'),
                            'last_name' => $securityUsers->aliasField('last_name'),
                        ])
                        ->LeftJoin([$securityUsers->getAlias() => $securityUsers->getTable()],[
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

    public function onGetType(Event $event, Entity $entity)
    {
        $connection = ConnectionManager::get('default');
        $results = $connection->query("SELECT name FROM curricular_types WHERE id=".$entity->institution_curricular->curricular_type_id);
        $curr_type = $results->fetch();
        return (!empty( $curr_type)) ?  $curr_type[0] : '--';

    }

    public function onGetCurricularType(Event $event, Entity $entity)
    {  
        if($entity->type != ''){
            return $entity->type;
        }else{
            $ic_id = $entity->institution_curricular_id;
            $connection = ConnectionManager::get('default');
            $ctype_rec = $connection->query("SELECT institution_curriculars.curricular_type_id,curricular_types.name  FROM institution_curriculars LEFT JOIN curricular_types ON curricular_types.id=institution_curriculars.curricular_type_id WHERE institution_curriculars.id=".$ic_id);
            $ctype_data = $ctype_rec->fetch();
            return (!empty( $ctype_data)) ?  $ctype_data[1] : '--';   
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['visible' => true]);
        $this->field('student_id', ['visible' => false]);
        $this->field('student_name', ['visible' => true]);
        $this->field('openemis_no', ['visible' => true]);
        $this->field('education_grade', ['visible' => true]);
        $this->field('institution_class', ['visible' => true]);
        $this->field('curricular_category', ['visible' => true]);
        // $this->field('curricular_type_id', ['visible' => true]);
        $this->field('curricular_type', ['visible' => true]);
        $this->field('institution_curricular_id', ['visible' => true]);
        $this->field('curricular_position_id', ['visible' => true]);
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
        $InstitutionCurriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $curricular_type = TableRegistry::getTableLocator()->get('FieldOption.CurricularTypes');
        $data = $InstitutionCurriculars->find()
                ->select(['name' => $curricular_type->aliasField('name')])
                ->LeftJoin([$curricular_type->getAlias() => $curricular_type->getTable()],
                    [$curricular_type->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
                ])
                ->where([$InstitutionCurriculars->aliasField('id') => $curricularsID])
                ->first();
        return $data->name;

    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        $curricularsID =  $entity->institution_curricular_id;
        $InstitutionCurriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $academicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        if(!empty($curricularsID)){
            $condition = [$InstitutionCurriculars->aliasField('id') => $curricularsID];
        }else{
            $condition = [$InstitutionCurriculars->aliasField('id IS') => $curricularsID];
        }
        $data = $InstitutionCurriculars->find()
                ->select(['name' => $academicPeriod->aliasField('name')])
                ->LeftJoin([$academicPeriod->getAlias() => $academicPeriod->getTable()],
                    [$academicPeriod->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('academic_period_id')
                ])
                ->where($condition)
                ->first();
        return $data->name;

    }

    public function onGetStudentName(Event $event, Entity $entity)
    {
        $StudentId = $entity->student_id;
        $users = TableRegistry::getTableLocator()->get('User.Users'); 
        $data = $this->find()->select(['first_name'=>$users->aliasField('first_name'),'middle_name'=>$users->aliasField('middle_name'),'third_name'=>$users->aliasField('third_name'),'last_name'=>$users->aliasField('last_name')])
                ->leftJoin([$users->getAlias() => $users->getTable()],
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
        $curricularStudent = TableRegistry::getTableLocator()->get('Institution.InstitutionCurricularStudents'); 
        $checkStudent =  $curricularStudent->find()->where([$curricularStudent->aliasField('student_id')=>$entity->student_id])->first();     
             
        if(!empty($checkStudent)){
            $message = __('Its Associated with Other Data');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $curricularStudent = TableRegistry::getTableLocator()->get('Institution.InstitutionCurricularStudents');
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $countMaleFemale = $curricularStudent->find()
                     ->select(['male_students' => "
                                 (COUNT(DISTINCT(CASE WHEN ".$users->aliasField('gender_id')." = 1 THEN ".$curricularStudent->aliasField('student_id')." END))) ",
                                'female_students' => "
                                 (COUNT(DISTINCT(CASE WHEN ".$users->aliasField('gender_id')." = 2 THEN ".$curricularStudent->aliasField('student_id')." END))) "

                                ])
                    ->InnerJoin([$users->getAlias() => $users->getTable()],
                    [$users->aliasField('id').' = ' . $curricularStudent->aliasField('student_id')])
                    ->where([$curricularStudent->aliasField('institution_curricular_id') => $entity->institution_curricular_id])
                    ->group([$curricularStudent->aliasField('institution_curricular_id')])->toArray();
        foreach($countMaleFemale as $value){
            $maleStudents  = $value->male_students;
            $femaleStudents  = $value->female_students;
        }
        $InstitutionCurriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $updateCurricular =   $InstitutionCurriculars->updateAll(
                                ['total_male_students' => $maleStudents,'total_female_students'=>$femaleStudents],    //field
                                [
                                 'id' => $entity->institution_curricular_id, 
                                ]);

    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) { 
            case 'academic_period_id':
                return __('Academic Period');
            case 'name':
                return __('Name');
            case 'student_name': 
                return __('Student Name');
            case 'education_grade':
                return __('Education Grade');
            case 'institution_class':
                return __('Institution Class');
            case 'curricular_category': 
                return __('Curricular Category');
            case 'type':
                return __('Type');
            case 'institution_curricular_id':
                return __('Institution Curricular');
            case 'curricular_position_id':
                return __('Curricular Position');
            case 'curricular_type':
                return __('Curricular Type');
            case 'curricular_type_id':
                return __('Curricular Type');
            case 'start_date':
                return __('Start Date');
            case 'end_date':
                return __('End Date');
            case 'category':
                return __('Category');
            case 'student_id':
                return __('Student');
            case 'hours':
                return __('Hours');
            case 'points':
                return __('Points');
            case 'location':
                return __('Location');
            case 'comments':
                return __('Comments');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
