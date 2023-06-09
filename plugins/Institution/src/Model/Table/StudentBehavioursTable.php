<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Core\Configure;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

use Page\Traits\EncodingTrait;
use App\Model\Traits\MessagesTrait;

class StudentBehavioursTable extends ControllerActionTable
{
    use OptionsTrait;
    use EncodingTrait;
    use MessagesTrait;
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']); //POCOR-5186
        $this->belongsTo('Students', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories']);
        $this->belongsTo('StudentBehaviourClassifications', ['className' => 'Student.StudentBehaviourClassifications','foreignKey' => 'student_behaviour_classification_id']);//POCOR-7223
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);//POCOR-5186
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('InstitutionStudents', ['className' => 'InstitutionStudent.InstitutionStudents', 'foreignKey' => 'student_id']);
        $this->hasMany('StudentBehaviourAttachments', [
            'className' => 'Institutions.StudentBehaviourAttachments',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->addBehavior('Workflow.Workflow'); //POCOR-5186
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index', 'view', 'add', 'edit', 'delete']
        ]);
        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
        $this->features = $WorkflowRules->getFeatureOptionsWithClassName();
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }
        $this->setDeleteStrategy('restrict');

        //if ($this->AccessControl->check(['Institutions', 'StudentBehaviours', 'Excel'])) { // to check execute permission
        ///}
        $roles = [1,2,3,4,5,6,7,8,9,10,11];
        $QueryResult = TableRegistry::get('SecurityRoleFunctions')->find()
                ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                    [
                        'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    ]
                ])
                ->where([
                    'SecurityRoleFunctions.security_role_id IN'=>$roles,
                    'SecurityFunctions._execute'=>'StaffBehaviours.excel',
                    'SecurityRoleFunctions._execute' => 1
                ])
                ->toArray();
        if(!empty($QueryResult)){
            $this->addBehavior('Excel', ['pages' => ['index']]);
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
        ];

        $events['Model.InstitutionStudentRisks.calculateRiskValue'] = 'institutionStudentRiskCalculateRiskValue';
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
        ->notEmpty('assignee_id')
            ->add('date_of_behaviour', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'provider' => 'table'// POCOR 6154 
                ]
            ]);
    }

    // Jeff: is this validation still necessary? perhaps it is already handled by onUpdateFieldAcademicPeriod date_options
    // public function validationDefault(Validator $validator) {
        // get start and end date of selected academic period
        // $selectedPeriod = $this->request->query('period');
        // if($selectedPeriod) {
        //  $selectedPeriodEntity = TableRegistry::get('AcademicPeriod.AcademicPeriods')->get($selectedPeriod);
        //  $startDateFormatted = date_format($selectedPeriodEntity->start_date,'d-m-Y');
        //  $endDateFormatted = date_format($selectedPeriodEntity->end_date,'d-m-Y');

        //  $validator
        //  ->add('date_of_behaviour',
        //          'ruleCheckInputWithinRange',
        //              ['rule' => ['checkInputWithinRange', 'date_of_behaviour', $startDateFormatted, $endDateFormatted]]

        //      )
        //  ;
        //  return $validator;
        // }
    // }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $event->subject()->Html->link($entity->student->openemis_no, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentUser',
                'view',
                $this->paramsEncode(['id' => $entity->student->id])
            ]);
        } else {
            return $entity->student->openemis_no;
        }
    }

    /*public function beforeAction($event)
    {
        $this->field('openemis_no');
        $this->field('student_id');
        $this->field('student_behaviour_category_id', ['type' => 'select']);

        if ($this->action == 'view' || $this->action = 'edit') {
            $this->setFieldOrder(['openemis_no', 'student_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'student_behaviour_category_id']);
        }
    } */

    // POCOR 6154 start set fields on index page 
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no', ['visible' => true]);
        $this->field('student_id', ['visible' => true]);
        $this->field('student_behaviour_category_id', ['visible' => true]);
        $this->field('student_behaviour_classification_id', ['visible' => true]);//POCOR-7223
        $this->field('description', ['visible' => false]);
        $this->field('action', ['visible' => false]);
        $this->field('time_of_behaviour', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('category_id', ['visible' => false]);//POCOR-5186
        $this->field('assignee_id', ['visible' => false]);//POCOR-5186
        $this->field('status_id', ['visible' => true]);//POCOR-5186

        $this->fields['student_id']['sort'] = ['field' => 'Students.first_name']; // POCOR-2547 adding sort

        $this->setFieldOrder(['openemis_no', 'student_id', 'date_of_behaviour', 'title', 'student_behaviour_category_id']);

        // Start POCOR-5188 
		$is_manual_exist = $this->getManualUrl('Institutions','Behaviour','Students - Academic');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
        $this->setFieldOrder(['status','openemis_no', 'student_id', 'date_of_behaviour', 'title', 'student_behaviour_category_id']);
        $this->fields['assignee_id']['sort'] = ['field' => 'Assignees.first_name'];//POCOR-5186
    }
    // setting up index page with required fields
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['elements']['controls'] = ['name' => 'Institution.Behaviours/controls', 'data' => [], 'options' => [], 'order' => 1];

        // Setup period options
        // $periodOptions = ['0' => __('All Periods')];
        $periodOptions = $this->AcademicPeriods->getYearList();
        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }

        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $selectedPeriod = $this->queryString('academic_period_id', $periodOptions);

        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
            'callable' => function ($id) use ($Classes, $institutionId) {
                $count = $Classes->find()
                ->where([
                    $Classes->aliasField('institution_id') => $institutionId,
                    $Classes->aliasField('academic_period_id') => $id
                ])
                ->count();
                return $count;
            }
        ]);

        if (!empty($selectedPeriod)) {
            $query->find('inPeriod', ['field' => 'date_of_behaviour', 'academic_period_id' => $selectedPeriod]);
        }

        // Setup class options
        $classOptions = ['0' => __('All Classes')];
        if (!empty($selectedPeriod)) {
            $classOptions = $classOptions + $Classes
            ->find('list')
            ->where([
                $Classes->aliasField('institution_id') => $institutionId,
                $Classes->aliasField('academic_period_id') => $selectedPeriod
            ])
            ->toArray();

            $query->find('inPeriod', ['field' => 'date_of_behaviour', 'academic_period_id' => $selectedPeriod]);
        }

        $selectedClass = $this->queryString('class_id', $classOptions);
        $this->advancedSelectOptions($classOptions, $selectedClass);
        // End setup class

        // POCOR-5186 Setup Categories options
        
        if (!empty($selectedPeriod)) {
            $categories = ['0' => __('All Categories'),'1' => 'To Do','2'=>'In Progress','3'=>'Done'];
            $query->find('inPeriod', ['field' => 'date_of_behaviour', 'academic_period_id' => $selectedPeriod]);
        }

        $selectedCategories = $this->queryString('category_id', $categories);
        $this->advancedSelectOptions($categories, $selectedCategories);
        // End setup class

        $this->controller->set(compact('periodOptions', 'classOptions','categories'));

        if ($selectedClass > 0) {
            $query->innerJoin(
                ['class_student' => 'institution_class_students'],
                [
                    'class_student.student_id = ' . $this->aliasField('student_id'),
                    'class_student.institution_class_id = ' . $selectedClass
                ]
            );
        }

        // will need to check for search by name: AdvancedNameSearchBehavior

        // POCOR-2547 Adding sortWhiteList to $options
        $sortList = ['Students.first_name'];
        if (array_key_exists('sortWhitelist', $options)) {
            $sortList = array_merge($options['sortWhitelist'], $sortList);
        }
        $options['sortWhitelist'] = $sortList;

        // POCOR-2547 sort list of staff and student by name
        if (!isset($this->request->query['sort'])) {
            $query->order([$this->Students->aliasField('first_name'), $this->Students->aliasField('last_name')]);
        }
        // end POCOR-2547

        $queryParams = $this->request->query;
        $search = $this->getSearchKey();

        // CUSTOM SEACH - 
        $extra['auto_search'] = false; // it will append an AND
        if (!empty($search)) {
            $query->find('ByUserData', ['search' => $search]);
        }
    }
    // POCOR 6154 end

    /* public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $toolbarElements = [
            ['name' => 'Institution.Behaviours/controls', 'data' => [], 'options' => []]
        ];
        $this->controller->set('toolbarElements', $toolbarElements);

        // Setup period options
        // $periodOptions = ['0' => __('All Periods')];
        $periodOptions = $this->AcademicPeriods->getYearList();
        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }

        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $selectedPeriod = $this->queryString('academic_period_id', $periodOptions);

        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
            'callable' => function ($id) use ($Classes, $institutionId) {
                $count = $Classes->find()
                ->where([
                    $Classes->aliasField('institution_id') => $institutionId,
                    $Classes->aliasField('academic_period_id') => $id
                ])
                ->count();
                return $count;
            }
        ]);

        // Setup class options
        $classOptions = ['0' => __('All Classes')];
        if (!empty($selectedPeriod)) {
            $classOptions = $classOptions + $Classes
            ->find('list')
            ->where([
                $Classes->aliasField('institution_id') => $institutionId,
                $Classes->aliasField('academic_period_id') => $selectedPeriod
            ])
            ->toArray();

            $query->find('inPeriod', ['field' => 'date_of_behaviour', 'academic_period_id' => $selectedPeriod]);
        }

        $selectedClass = $this->queryString('class_id', $classOptions);
        $this->advancedSelectOptions($classOptions, $selectedClass);
        // End setup class

        $this->controller->set(compact('periodOptions', 'classOptions'));

        if ($selectedClass > 0) {
            $query->innerJoin(
                ['class_student' => 'institution_class_students'],
                [
                    'class_student.student_id = ' . $this->aliasField('student_id'),
                    'class_student.institution_class_id = ' . $selectedClass
                ]
            );
        }

        // will need to check for search by name: AdvancedNameSearchBehavior

        // POCOR-2547 Adding sortWhiteList to $options
        $sortList = ['Students.first_name'];
        if (array_key_exists('sortWhitelist', $options)) {
            $sortList = array_merge($options['sortWhitelist'], $sortList);
        }
        $options['sortWhitelist'] = $sortList;

        // POCOR-2547 sort list of staff and student by name
        if (!isset($this->request->query['sort'])) {
            $query->order([$this->Students->aliasField('first_name'), $this->Students->aliasField('last_name')]);
        }
        // end POCOR-2547
    } */

    public function addAfterAction(Event $event, Entity $entity)
    {
        // POCOR 6154 
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('class', ['entity' => $entity]);
        $this->field('date_of_behaviour', ['entity' => $entity]);
        $this->field('assignee_id', ['entity' => $entity]);//POCOR-5186
        $this->setFieldOrder(['academic_period_id', 'class', 'student_id', 'student_behaviour_category_id','student_behaviour_classification_id','date_of_behaviour', 'time_of_behaviour','description','action','assignee_id']);//POCOR-7223
        // POCOR 6154 

    }

    // PHPOE-1916
    // public function viewAfterAction(Event $event, Entity $entity) {
    //  $this->request->data[$this->alias()]['student_id'] = $entity->student_id;
    //  $this->request->data[$this->alias()]['date_of_behaviour'] = $entity->date_of_behaviour;
    // }

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['AcademicPeriods','Students','StudentBehaviourCategories','Assignees']);// POCOR 6154 
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('date_of_behaviour', ['entity' => $entity]);
        $this->fields['student_id']['attr']['value'] = $entity->student->name_with_id;
        $this->setFieldOrder(['academic_period_id', 'class', 'student_id', 'student_behaviour_category_id', 'date_of_behaviour', 'time_of_behaviour','description','action','assignee_id']);//POCOR-7223
    
        // PHPOE-1916
        // Not yet implemented due to possible performance issue
        // $InstitutionClassStudentTable = TableRegistry::get('Institution.InstitutionClassStudents');
        // $AcademicPeriodId = $InstitutionClassStudentTable->find()
        //              ->where([$InstitutionClassStudentTable->aliasField('student_id') => $entity->student_id])
        //              ->innerJoin(['InstitutionClasses' => 'institution_classes'],[
        //                      'InstitutionClasses.id = '.$InstitutionClassStudentTable->aliasField('institution_class_id'),
        //                      'InstitutionClasses.institution_id' => $entity->institution_id
        //                  ])
        //              ->innerJoin(['AcademicPeriods' => 'academic_periods'], [
        //                      'AcademicPeriods.id = InstitutionClasses.academic_period_id',
        //                      'AcademicPeriods.start_date <= ' => $entity->date_of_behaviour->format('Y-m-d'),
        //                      'AcademicPeriods.end_date >= ' => $entity->date_of_behaviour->format('Y-m-d')
        //                  ])
        //              ->select(['id' => 'AcademicPeriods.id', 'editable' => 'AcademicPeriods.editable'])
        //              ->first()
        //              ->toArray();

        // if (! $AcademicPeriodId['editable']) {
        //  $urlParams = $this->url('view');
        //  $event->stopPropagation();
        //  return $this->controller->redirect($urlParams);
        // }
    }

    // PHPOE-1916
    // Not yet implemented due to possible performance issue
    // public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
    //  if ($action == 'view') {
    //      $institutionId = $this->Session->read('Institution.Institutions.id');
    //      $studentId = $this->request->data[$this->alias()]['student_id'];
    //      $dateOfBehaviour = $this->request->data[$this->alias()]['date_of_behaviour'];
    //      $InstitutionClassStudentTable = TableRegistry::get('Institution.InstitutionClassStudents');
    //      $AcademicPeriodId = $InstitutionClassStudentTable->find()
    //              ->where([$InstitutionClassStudentTable->aliasField('student_id') => $studentId])
    //              ->innerJoin(['InstitutionClasses' => 'institution_classes'],[
    //                      'InstitutionClasses.id = '.$InstitutionClassStudentTable->aliasField('institution_class_id'),
    //                      'InstitutionClasses.institution_id' => $institutionId
    //                  ])
    //              ->innerJoin(['AcademicPeriods' => 'academic_periods'], [
    //                      'AcademicPeriods.id = InstitutionClasses.academic_period_id',
    //                      'AcademicPeriods.start_date <= ' => $dateOfBehaviour->format('Y-m-d'),
    //                      'AcademicPeriods.end_date >= ' => $dateOfBehaviour->format('Y-m-d')
    //                  ])
    //              ->select(['id' => 'AcademicPeriods.id', 'editable' => 'AcademicPeriods.editable'])
    //              ->first()
    //              ->toArray();

    //      if (! $AcademicPeriodId['editable']) {
    //          if(isset($toolbarButtons['edit'])) {
    //              unset($toolbarButtons['edit']);
    //          }
    //      }
    //  }
    // }
    /* pocor-6154 start set fields order on edit page */
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
    }
    
    public function setupFields(Entity $entity, ArrayObject $extra)
    { 
        $this->field('openemis_no', ['visible' => ['view' => true,'edit' => false]]);
        $this->field('student_id',['after' => 'openemis_no','visible' => ['view' => true,'edit' => true]]);
        $this->field('student_behaviour_category_id',['after' => 'student_id','visible' => ['view' => true,'edit' => true]]);
         $this->field('assignee_id',['after' => 'action','visible' => ['view' => true,'edit' => true]]);//POCOR-5186
         $this->field('status_id',['after' => 'action','visible' => ['view' => false,'edit' => false]]);//POCOR-5186

        // $this->setFieldOrder(['student_id','time_of_behaviour','date_of_behaviour','title', 'student_behaviour_category_id', 'description', 'action']);
    }
    /* pocor-6154 end */

    /* pocor-6154 set fields on add page */
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['student_id']['type'] = 'select';
        $this->field('student_id', ['attr' => ['label' => __('Student')]]);

        $this->fields['student_behaviour_category_id']['type'] = 'select';
        $this->field('student_behaviour_category_id', ['attr' => ['label' => __('Category')]]);//POCOR-7223
        $this->fields['student_behaviour_classification_id']['type'] = 'select';
        $this->field('student_behaviour_classification_id', ['attr' => ['label' => __('Classification')]]);//POCOR-7223
        $this->fields['assignee_id']['type'] = 'select';//POCOR-5186
   
    }
    /* pocor-6154 */

    public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit' || $action == 'add') {
            $attr['visible'] = false;
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');

        $Classes = TableRegistry::get('Institution.InstitutionClasses');

        if ($action == 'add') {
            $entity = $attr['entity'];
            $periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);

            if ($entity->has('academic_period_id')) {
                $selectedPeriod = $entity->academic_period_id;
            } else {
                if (is_null($request->query('academic_period_id'))) {
                    $selectedPeriod = $this->AcademicPeriods->getCurrent();
                } else {
                    $selectedPeriod = $request->query('academic_period_id');
                }
                $entity->academic_period_id = $selectedPeriod;
            }

            $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
                'callable' => function ($id) use ($Classes, $institutionId) {
                    $count = $Classes->find()
                    ->where([
                        $Classes->aliasField('institution_id') => $institutionId,
                        $Classes->aliasField('academic_period_id') => $id
                    ])
                    ->count();
                    return $count;
                }
            ]);

            $attr['select'] = false;
            $attr['options'] = $periodOptions;
            $attr['value'] = $selectedPeriod;
            $attr['attr']['value'] = $selectedPeriod;
            $attr['onChangeReload'] = 'changePeriod';

        } elseif ($action == 'edit') {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function addOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $data[$this->alias()]['class'] = 0;
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $institutionId = $this->Session->read('Institution.Institutions.id');
            $entity = $attr['entity'];
            $selectedPeriod = $entity->academic_period_id;

            $classOptions = ['0' => __('-- Select --')];

            if (!empty($selectedPeriod)) {
                $Classes = TableRegistry::get('Institution.InstitutionClasses');
                $Students = TableRegistry::get('Institution.InstitutionClassStudents');
                $classOptions = $classOptions + $Classes
                    ->find('list')
                    ->where([
                        $Classes->aliasField('institution_id') => $institutionId,
                        $Classes->aliasField('academic_period_id') => $selectedPeriod
                    ])
                    ->order([$Classes->aliasField('class_number') => 'ASC'])
                    ->toArray();

                $selectedClass = 0;
                if ($request->is(['post', 'put'])) {
                    $selectedClass = $request->data($this->aliasField('class'));
                }
                $this->advancedSelectOptions($classOptions, $selectedClass, [
                    'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
                    'callable' => function ($id) use ($Students) {
                        return $Students
                            ->find()
                            ->where([
                                $Students->aliasField('institution_class_id') => $id
                            ])
                            ->count();
                    }
                ]);
            }

            $attr['select'] = false;
            $attr['options'] = $classOptions;
            $attr['onChangeReload'] = 'changeClass';
        }
        return $attr;
    }

    public function onUpdateFieldDateOfBehaviour(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $selectedPeriod = $entity->academic_period_id;
            $academicPeriod = $this->AcademicPeriods->get($selectedPeriod);

            $startDate = $academicPeriod->start_date;
            $endDate = $academicPeriod->end_date;

            if ($action == 'add') {
                $todayDate = Date::now();

                if (!empty($request->data[$this->alias()]['date_of_behaviour'])) {
                    $inputDate = Date::createfromformat('d-m-Y', $request->data[$this->alias()]['date_of_behaviour']); //string to date object

                    // if today date is not within selected academic period, default date will be start of the year
                    if ($inputDate < $startDate || $inputDate > $endDate) {
                        $attr['value'] = $startDate->format('d-m-Y');

                        // if today date is within selected academic period, default date will be current date
                        if ($todayDate >= $startDate && $todayDate <= $endDate) {
                            $attr['value'] = $todayDate->format('d-m-Y');
                        }
                    }
                } else {
                    if ($todayDate <= $startDate || $todayDate >= $endDate) {
                        $attr['value'] = $startDate->format('d-m-Y');
                    } else {
                        $attr['value'] = $todayDate->format('d-m-Y');
                    }
                }
            }

            $attr['date_options'] = ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')];
            $attr['date_options']['todayBtn'] = false;
        }

        return $attr;
    }

    // Start PHPOE-1897
    public function viewBeforeAction(Event $event)
    {
        $tabElements = $this->getStudentBehaviourTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['visible' => false]);// POCOR 6154 
        $this->request->data[$this->alias()]['student_id'] = $entity->student_id;
        $entity['openemis_no'] = $entity->student->openemis_no; //adding openemis no for view page only

        $this->setupFields($entity, $extra);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        // $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $studentId = $entity->student_id;
        $institutionId = $entity->institution_id;
        $StudentTable = TableRegistry::get('Institution.Students');

        if (! $StudentTable->checkEnrolledInInstitution($studentId, $institutionId)) {
            if (isset($buttons['edit'])) {
                unset($buttons['edit']);
            }
            if (isset($buttons['remove'])) {
                unset($buttons['remove']);
            }
        }
        return $buttons;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($action == 'view') {
            $institutionId = $this->Session->read('Institution.Institutions.id');
            $studentId = $this->request->data[$this->alias()]['student_id'];
            $StudentTable = TableRegistry::get('Institution.Students');
            if (! $StudentTable->checkEnrolledInInstitution($studentId, $institutionId)) {
                if (isset($toolbarButtons['edit'])) {
                    unset($toolbarButtons['edit']);
                }
            }
        }
    }
    // End PHPOE-1897

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $studentOptions = [];

            $selectedClass = 0;
            if ($request->is(['post', 'put'])) {
                $selectedClass = $request->data($this->aliasField('class'));
            }
            if (! $selectedClass==0 && ! empty($selectedClass)) {
                $Students = TableRegistry::get('Institution.InstitutionClassStudents');
                $studentOptions = $studentOptions + $Students
                ->find('list', ['keyField' => 'student_id', 'valueField' => 'student_name'])
                ->contain(['Users'])
                ->where([$Students->aliasField('institution_class_id') => $selectedClass])
                ->order(['Users.first_name', 'Users.last_name']) // POCOR-2547 sort list of staff and student by name
                ->toArray();
            }

            $attr['options'] = $studentOptions;
        } elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }


    public function institutionStudentRiskCalculateRiskValue(Event $event, ArrayObject $params)
    {
        $institutionId = $params['institution_id'];
        $studentId = $params['student_id'];
        $academicPeriodId = $params['academic_period_id'];
        $criteriaName = $params['criteria_name'];

        $valueIndex = $this->getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName);

        return $valueIndex;
    }

    public function getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName)
    {
        $behaviourResults = $this
            ->find()
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->all();

        $getValueIndex = [];
        foreach ($behaviourResults as $key => $behaviourResultsObj) {
            $studentBehaviourCategoryId = $behaviourResultsObj->student_behaviour_category_id;
            $behaviourClassificationId = $this->StudentBehaviourCategories->get($studentBehaviourCategoryId)->behaviour_classification_id;
            $getValueIndex[$behaviourClassificationId] = !empty($getValueIndex[$behaviourClassificationId]) ? $getValueIndex[$behaviourClassificationId] : 0;
            $getValueIndex[$behaviourClassificationId] = $getValueIndex[$behaviourClassificationId] + 1;
        }

        return $getValueIndex;
    }

    public function getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName)
    {
        $behaviourClassificationId = $threshold; // it will classified by the classification id
        $behaviourResults = $this
            ->find()
            ->contain(['StudentBehaviourCategories'])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                'behaviour_classification_id' => $behaviourClassificationId
            ])
            ->all();

        $referenceDetails = [];
        foreach ($behaviourResults as $key => $obj) {
            $title = $obj->student_behaviour_category->name;
            $date = $obj->date_of_behaviour->format('d/m/Y');

            $referenceDetails[$obj->id] = __($title) . ' (' . $date . ')';
        }

        // tooltip only receieved string to be display
        $reference = '';
        foreach ($referenceDetails as $key => $referenceDetailsObj) {
            $reference = $reference . $referenceDetailsObj . '<br/>';
        }

        return $reference;
    }

    public function getStudentBehaviourTabElements($options = [])
    {
  
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $paramPass = $this->request->param('pass');
        $ids = isset($paramPass[1]) ? $this->paramsDecode($paramPass[1]) : [];
        $studentBehaviourId = $ids['id'];
        $queryString = $this->encode(['student_behaviour_id' => $studentBehaviourId]);

        $tabElements = [
            'StudentBehaviours' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentBehaviours', 'view', $paramPass[1]],
                'text' => __('Overview')
            ],
            'StudentBehaviourAttachments' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'StudentBehaviourAttachments', 'action' => 'index', 'querystring' => $queryString, 'institutionId' => $encodedInstitutionId],
                'text' => __('Attachments')
            ]
        ];

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        $extraField[] = [
            'key' => 'Students.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraField[] = [
            'key' => 'Students.student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student')
        ];

        $extraField[] = [
            'key' => 'StudentBehaviour.date_of_behaviour',
            'field' => 'date_of_behaviour',
            'type' => 'date',
            'label' => __('Date Of Behaviour')
        ];

        $extraField[] = [
            'key' => 'StudentBehaviour.title',
            'field' => 'title',
            'type' => 'string',
            'label' => __('Title')
        ];

        $extraField[] = [
            'key' => 'StudentBehaviourCategories.name',
            'field' => 'category_name',
            'type' => 'string',
            'label' => __('Category')// POCOR 6154 
        ];


        $fields->exchangeArray($extraField);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        // POCOR 6154 
        $academicPeriod = ($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent() ;
        // POCOR 6154 
        $User = TableRegistry::get('security_users');

        // POCOR 6154 
        $query
        ->select([
            'title' => 'StudentBehaviours.title',
            'category_name' => 'StudentBehaviourCategories.name',
            'date_of_behaviour' => 'StudentBehaviours.date_of_behaviour', 
            'openemis_no' => 'Students.openemis_no', 
            'student_name' => $User->find()->func()->concat([
                'first_name' => 'literal',
                " ",
                'last_name' => 'literal'
            ])
        ])
        ->LeftJoin([$this->AcademicPeriods->alias() => $this->AcademicPeriods->table()],[
            $this->AcademicPeriods->aliasField('id').' = ' . 'StudentBehaviours.academic_period_id'
        ])
        ->LeftJoin([$this->Students->alias() => $this->Students->table()],[
            $this->Students->aliasField('id').' = ' . 'StudentBehaviours.student_id'
        ])
        ->LeftJoin([$this->Institutions->alias() => $this->Institutions->table()],[
            $this->Institutions->aliasField('id').' = ' . 'StudentBehaviours.institution_id'
        ])
        ->LeftJoin([$this->StudentBehaviourCategories->alias() => $this->StudentBehaviourCategories->table()],[
            $this->StudentBehaviourCategories->aliasField('id').' = ' . 'StudentBehaviours.student_behaviour_category_id'
        ])
        ->where([
            'StudentBehaviours.academic_period_id' =>  $academicPeriod,
            'StudentBehaviours.institution_id' =>  $institutionId
        ]);
        // POCOR 6154 
    }

    /*POCOR-5177 starts*/
    public function deleteBeforeAction(Event $event, ArrayObject $extra)
    {   
        $id = $this->request->data['primaryKey'];
        $jsonData = base64_decode($id);
        preg_match_all('/{(.*?)}/', $jsonData, $matches);
        $requestData = json_decode($matches[0][0]);
        $ConfigItemsTable = TableRegistry::get('Configuration.ConfigItems');
        $compareDate = $ConfigItemsTable->find()
                        ->select([$ConfigItemsTable->aliasField('value')])
                        ->where([
                            $ConfigItemsTable->aliasField('name') => 'Student Behavior',
                            $ConfigItemsTable->aliasField('code') => 'student_behavior',
                            $ConfigItemsTable->aliasField('label') => 'Student Behavior'
                        ])->first();
        if (!empty($compareDate) && $compareDate->value != 0) {
            $addDays = $compareDate->value;
            $getRecord = $this->find()
                            ->select([$this->aliasField('date_of_behaviour')])
                            ->where([$this->aliasField('id') => $requestData->id])
                            ->first();
            $date = date('Y-m-d', strtotime($getRecord->date_of_behaviour));
            $newDate = date('Y-m-d', strtotime($date. ' + '. $addDays .' days'));
            $today = new Date();
            $todayDate = date('Y-m-d', strtotime($today));
            if ($newDate > $todayDate) {
                $event->stopPropagation();
                $this->Alert->warning('StudentBehaviours.cannotDelete');
                $action = $this->ControllerAction->url('index');
                return $this->controller->redirect($action);
            }
        }
    }
    /*POCOR-5177 ends*/

    /**
     * POCOR-5186 Assignee id
    */
    public function onGetAssigneeId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->assignee->name;
        } 
    }

    /**
     * POCOR-5186 Assignee id
     *add assignee dropdown in edit and view page
    */
    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Institutions > Behaviour > Students';
            $workflowModelsTable = TableRegistry::get('workflow_models');
            $workflowStepsTable = TableRegistry::get('workflow_steps');
            $Workflows = TableRegistry::get('Workflow.Workflows');
            $workModelId = $Workflows
                            ->find()
                            ->select(['id'=>$workflowModelsTable->aliasField('id'),
                            'workflow_id'=>$Workflows->aliasField('id'),
                            'is_school_based'=>$workflowModelsTable->aliasField('is_school_based')])
                            ->LeftJoin([$workflowModelsTable->alias() => $workflowModelsTable->table()],
                                [
                                    $workflowModelsTable->aliasField('id') . ' = '. $Workflows->aliasField('workflow_model_id')
                                ])
                            ->where([$workflowModelsTable->aliasField('name')=>$workflowModel])->first();
            $workflowId = $workModelId->workflow_id;
            $isSchoolBased = $workModelId->is_school_based;
            $workflowStepsOptions = $workflowStepsTable
                            ->find()
                            ->select([
                                'stepId'=>$workflowStepsTable->aliasField('id'),
                            ])
                            ->where([$workflowStepsTable->aliasField('workflow_id') => $workflowId])
                            ->first();
            $stepId = $workflowStepsOptions->stepId;
            $session = $request->session();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
            $institutionId = $institutionId;
            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                    $Areas = TableRegistry::get('Area.Areas');
                    $Institutions = TableRegistry::get('Institution.Institutions');
                    if ($isSchoolBased) {
                        if (is_null($institutionId)) {                        
                            Log::write('debug', 'Institution Id not found.');
                        } else {
                            $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                            $securityGroupId = $institutionObj->security_group_id;
                            $areaObj = $institutionObj->area;
                            // School based assignee
                            $where = [
                                'OR' => [[$SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId],
                                        ['Institutions.id' => $institutionId]],
                                $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                            ];
                            $schoolBasedAssigneeQuery = $SecurityGroupUsers
                                    ->find('userList', ['where' => $where])
                                    ->leftJoinWith('SecurityGroups.Institutions');
                            $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();
                            
                            // Region based assignee
                            $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                            $regionBasedAssigneeQuery = $SecurityGroupUsers
                                        ->find('UserList', ['where' => $where, 'area' => $areaObj]);
                            
                            $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                            // End
                            $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                        }
                    } else {
                        $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                        $assigneeQuery = $SecurityGroupUsers
                                ->find('userList', ['where' => $where])
                                ->order([$SecurityGroupUsers->aliasField('security_role_id') => 'DESC']);
                        $assigneeOptions = $assigneeQuery->toArray();
                    }
                }
            }
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select Assignee') . ' --'] + $assigneeOptions;
            $attr['onChangeReload'] = 'changeStatus';
            return $attr;
        }
    }

    public function getWorkflowActionEntity(Entity $entity){
        if ($entity->has('action')) {
            $selectedAction = $entity->action;
            $workflowActions = $entity->workflow_actions;

            foreach($workflowActions as $key => $actionEntity){
                if ($actionEntity->id == $selectedAction) {
                    return $actionEntity;
                }
            }
        }
        return null;
    }
    
    public function findByUserData(Query $query, array $options)
    {
        if (array_key_exists('search', $options)) {
            $search = $options['search'];
            $query
            ->join([
                [
                    'table' => 'security_users', 'alias' => 'Students', 'type' => 'LEFT',
                    'conditions' => ['security_users.id = ' . $this->aliasField('student_id')]
                ],
                [
                    'table' => 'student_behaviour_category', 'alias' => 'StudentBehaviourCategories', 'type' => 'LEFT',
                    'conditions' => ['student_behaviour_category.id = ' . $this->aliasField('student_behaviour_category_id')]
                ],
                
                
            ])
            ->where([
                    'OR' => [
                        ['Students.openemis_no LIKE' => '%' . $search . '%'],
                        ['Students.first_name LIKE' => '%' . $search . '%'],
                        ['Students.last_name LIKE' => '%' . $search . '%'],
                        ['Students.middle_name LIKE' => '%' . $search . '%'],
                        ['StudentBehaviourCategories.name LIKE' => '%' . $search . '%'],
                        [$this->aliasField('title').' LIKE' => '%' . $search . '%'],
                        [$this->aliasField('date_of_behaviour').' LIKE' => '%' . $search . '%'],
                        
                    ]
                ]
            );
        }

        return $query;
    }
    
    //POCOR-7223 start
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'student_behaviour_category_id':
                return __('Category');
            case 'student_behaviour_classification_id':
                return __('Classification');
            case 'date_of_behaviour':
                return __('Date');
            case 'time_of_behaviour':
                    return __('Time');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    //POCOR-7223 end
}


