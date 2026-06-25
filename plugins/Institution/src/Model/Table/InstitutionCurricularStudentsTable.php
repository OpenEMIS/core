<?php
namespace Institution\Model\Table;

use ArrayObject;
use stdClass;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
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
use Cake\Http\Session;
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
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InstitutionCurricularStudents' =>['id']
            ]
        ]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        if ($this->action == 'index') {
            $tabElements = $this->controller->getCurricularsTabElements();
            $this->controller->set('tabElements', $tabElements);
            $this->controller->set('selectedAction', 'InstitutionCurricularStudents');
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $paramsPass = $this->request->getQuery()['queryString'];
        $curricularIdGet = $this->paramsDecode($paramsPass)['id'];
        $institutionId = $this->getInstitutionID();
        $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $curricularPositions = TableRegistry::getTableLocator()->get('FieldOption.CurricularPositions');
        $InstitutionCurriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $curricular_types = TableRegistry::getTableLocator()->get('FieldOption.CurricularTypes');
        $Users = TableRegistry::getTableLocator()->get('Security.Users');
        $conditions = [];
        $conditions[$this->aliasField('institution_curricular_id')]  = $curricularIdGet;
        $conditions[$institutionStudents->aliasField('institution_id')]  = $institutionId;
        $conditions[$InstitutionCurriculars->aliasField('institution_id')]  = $institutionId;

        $query
            ->select([
                        $this->aliasField('id'),
                        $this->aliasField('student_id'),
                        'start_date'=>$this->aliasField('start_date'),
                        'end_date'=>$this->aliasField('end_date'),
                        'openemis_no'=>  $Users->aliasField('openemis_no'),
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
                ->LeftJoin([$curricularPositions->getAlias() => $curricularPositions->getTable()],
                    [$curricularPositions->aliasField('id').' = ' . $this->aliasField('curricular_position_id')
                ])
                ->LeftJoin([$InstitutionCurriculars->getAlias() => $InstitutionCurriculars->getTable()],
                    [$InstitutionCurriculars->aliasField('id').' = ' . $this->aliasField('institution_curricular_id')
                ])
                ->LeftJoin([$curricular_types->getAlias() => $curricular_types->getTable()],
                [$curricular_types->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
            ])
                ->where($conditions)->group([$this->aliasField('student_id')]);

        $extra['order'] = [$this->aliasField('name') => 'asc'];

        $search = $this->getSearchKey();

        // CUSTOM SEACH
        $extra['auto_search'] = false; // it will append an AND
        if (!empty($search)) {
            $query->find('byStudentData', ['search' => $search]);
        }

        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];

        $this->field('student_id', ['visible' => false]);
        $this->field('student_name', ['visible' => true]);
        $this->field('curricular_category', ['visible' => true]);
        $this->field('curricular_position_id', ['visible' => true]);
        $this->field('type', ['visible' => ['index'=>true,'view' => true,'edit' => false,'add'=>false]]);
        $this->field('start_date', ['visible' => true]);
        $this->field('end_date', ['visible' => true]);
        $this->field('institution_curricular_id', ['visible' => true]);
        $this->field('hours', ['visible' => false]);
        $this->field('points', ['visible' => false]);
        $this->field('location', ['visible' => false]);
        $this->field('comments', ['visible' => false]);
        $this->field('openemis_no', ['visible' => ['index'=>true,'view' => false]]);

        $this->setFieldOrder([
            'student_name',
            'openemis_no',
            'curricular_category',
            'type',
            'institution_curricular_id',
            'curricular_position_id',
            'start_date',
            'end_date']); //POCOR-7604

    }

    //POCOR-8482[START]
    // public function onGetCurricularCategory(EventInterface $event, Entity $entity)
    // {
    //     return $entity['institution_curricular']['category'] ? __('Co-Curricular') : $entity->category ? __('Co-Curricular') : __('Extracurricular');

    // }
    public function onGetCurricularCategory(EventInterface $event, Entity $entity)
    {
        return $entity['institution_curricular']['category'] ? __('Co-Curricular') : ($entity->category ? __('Co-Curricular') : __('Extracurricular'));
    }
    //POCOR-8482[END]

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $paramsPass = $this->request->getQuery()['queryString'];
        $curricularIdGet = $this->paramsDecode($paramsPass)['id'];
        $curriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $academicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $curricularType = TableRegistry::getTableLocator()->get('FieldOption.CurricularTypes');
        $curricularData = $curriculars->find()
                            ->select(['name'=>$curriculars->aliasField('name'),'category'=>$curriculars->aliasField('category'),
                                'curricularType'=>$curricularType->aliasField('name')
                                        ])
                            /*->LeftJoin([$academicPeriod->getAlias() => $academicPeriod->getTable()],[
                                $academicPeriod->aliasField('id').' = ' . $curriculars->aliasField('academic_period_id')
                            ])*/
                            ->LeftJoin([$curricularType->getAlias() => $curricularType->getTable()],[
                                $curricularType->aliasField('id').' = ' . $curriculars->aliasField('curricular_type_id')
                            ])
                            ->where([$curriculars->aliasField('id') => $curricularIdGet])->first();

        //POCOR-8482[START]
        // $entity->name = $curricularData->name;
        // $entity->category = $curricularData->category ? __('Co-Curricular') : __('Extracurricular');
        // $entity->curricularType = $curricularData->curricularType;

        $name = $curricularData->name;
        $category = $curricularData->category ? __('Co-Curricular') : __('Extracurricular');
        $curricularType = $curricularData->curricularType;
        //POCOR-8482[END]

        $this->field('name', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $name, 'required' => true]]);
        $this->field('curricular_type_id', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $curricularType, 'required' => true]]);
        $this->field('category', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $category, 'required' => true]]);
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

   public function onUpdateFieldStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR- 8220 chnage academic period condition
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
        if ($action == 'add' || $action == 'edit') {
            //return $this->updateDateRangeField('start_date', $attr, $request);
            $entity = $attr['entity'];
            $academicPeriodId = $selectedAcademicPeriodId;
            $periodStartDate = $this->AcademicPeriods->get($academicPeriodId)->start_date;
            $periodEndDate = $this->AcademicPeriods->get($academicPeriodId)->end_date;

            $attr['type'] = 'date';
            $attr['date_options'] = [
                'startDate' => $periodStartDate->format('d-m-Y'),
                'endDate' => $periodEndDate->format('d-m-Y'),
                'todayBtn' => false
            ];
            return $attr;
        }
    }

    public function onUpdateFieldEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR- 8220 chnage academic period condition
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
        if ($action == 'add' || $action == 'edit') {
            //return $this->updateDateRangeField('end_date', $attr, $request);
            $entity = $attr['entity'];
            $academicPeriodId = $selectedAcademicPeriodId;
            $periodStartDate = $this->AcademicPeriods->get($academicPeriodId)->start_date;
            $periodEndDate = $this->AcademicPeriods->get($academicPeriodId)->end_date;

            $attr['type'] = 'date';
            $attr['date_options'] = [
                'startDate' => $periodStartDate->format('d-m-Y'),
                'endDate' => $periodEndDate->format('d-m-Y'),
                'todayBtn' => false
            ];
            return $attr;
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

    public function onUpdateFieldCurricularPositionId(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldStudentId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
        //$session = $this->controller->request->session();
        //$institutionId = $session->read('Institution.Institutions.id');

        $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $securityUsers = TableRegistry::getTableLocator()->get('User.Users');
        $institutionId = $this->getInstitutionID();
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
                        ->where([
                            'student_status_id'=>1,
                            'institution_id'=>$institutionId,
                            'academic_period_id'=>$selectedAcademicPeriodId,
                        ])
                        ->orderAsc($securityUsers->aliasField('first_name'))
                        ->orderAsc($securityUsers->aliasField('last_name'))
                        ->toArray();
        $studentList = [] ;
        foreach($studentData as $student){
            if($student->id){
                $studentList[$student->id] = $student->openemis_no.' - '.$student->first_name.' '.$student->last_name;
            }
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

    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $paramsPass = $this->request->getQuery()['queryString'];
        $curricularIdGet = $this->paramsDecode($paramsPass)['id'];
        $entity->id = Text::uuid();
        $entity->institution_curricular_id = $curricularIdGet;
    }

    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $paramsPass = $this->request->getQuery()['queryString'];
        $curricularIdGet = $this->paramsDecode($paramsPass)['id'];
        $entity->institution_curricular_id = $curricularIdGet;
        $entity->id = $entity->id;

    }

    public function onGetType(EventInterface $event, Entity $entity)
    {
        $connection = ConnectionManager::get('default');
        $results = $connection->query("SELECT name FROM curricular_types WHERE id=".$entity->institution_curricular->curricular_type_id);
        $curr_type = $results->fetch();
        return (!empty( $curr_type)) ?  $curr_type[0] : '--';

    }

    public function onGetCurricularType(EventInterface $event, Entity $entity)
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

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
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

    public function onGetCategory(EventInterface $event, Entity $entity)
    {
        return $entity->category ? __('Co-Curricular') : __('Extracurricular');
    }

    public function onGetOpenemisNo(EventInterface $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onGetCurricularTypeId(EventInterface $event, Entity $entity)
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

    public function onGetStudentName(EventInterface $event, Entity $entity)
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

    //POCOR-8056
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $modelAlias = 'InstitutionCurricularStudents';
        $userType = 'StudentUser';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }
    //POCOR-8056

    public function findByStudentData(Query $query, array $options)
    {
        if (isset($options['search'])) {
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

    public function beforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $action = $this->request->getAttribute('params')['action'];
        if($action != 'InstitutionCurricularStudents'){
            $curricularStudent = TableRegistry::getTableLocator()->get('Institution.InstitutionCurricularStudents');
            $checkStudent =  $curricularStudent->find()->where([$curricularStudent->aliasField('student_id')=>$entity->student_id])->first();
            if(!empty($checkStudent)){
                $message = __('Its Associated with Other Data');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                $event->stopPropagation();
            }
        }else{
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
            $usersInfo = $users->find()->where([$users->aliasField('id') => $entity->student_id])->first();
            if(($maleStudents > 0 && $usersInfo->gender_id == 1)){
                $InstitutionCurriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
                $updateCurricular =   $InstitutionCurriculars->updateAll(
                                        ['total_male_students' => $maleStudents - 1,'total_female_students'=>$femaleStudents],    //field
                                        [
                                        'id' => $entity->institution_curricular_id,
                                        ]);
            }
            if(($femaleStudents > 0  && $usersInfo->gender_id == 2)){
                $InstitutionCurriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
                $updateCurricular =   $InstitutionCurriculars->updateAll(
                                        ['total_male_students' => $maleStudents,'total_female_students'=>$femaleStudents-1],    //field
                                        [
                                        'id' => $entity->institution_curricular_id,
                                        ]);
            }
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
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

}
