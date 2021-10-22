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

class StudentBehavioursTable extends ControllerActionTable
{
    use OptionsTrait;
    use EncodingTrait;
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Students', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->hasMany('StudentBehaviourAttachments', [
            'className' => 'Institutions.StudentBehaviourAttachments',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index', 'view', 'add', 'edit', 'delete']
        ]);
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }
        $this->setDeleteStrategy('restrict');

        //if ($this->AccessControl->check(['Institutions', 'StudentBehaviours', 'Excel'])) { // to check execute permission
        $this->addBehavior('Excel', ['pages' => ['index']]);
        ///}
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
        // 	$selectedPeriodEntity = TableRegistry::get('AcademicPeriod.AcademicPeriods')->get($selectedPeriod);
        // 	$startDateFormatted = date_format($selectedPeriodEntity->start_date,'d-m-Y');
        // 	$endDateFormatted = date_format($selectedPeriodEntity->end_date,'d-m-Y');

        // 	$validator
        // 	->add('date_of_behaviour',
        // 			'ruleCheckInputWithinRange',
        // 				['rule' => ['checkInputWithinRange', 'date_of_behaviour', $startDateFormatted, $endDateFormatted]]

        // 		)
        // 	;
        // 	return $validator;
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

    /* public function beforeAction($event)
    {
        $this->field('openemis_no');
        $this->field('student_id');
        $this->field('student_behaviour_category_id', ['type' => 'select']);

        if ($this->action == 'view' || $this->action = 'edit') {
            $this->setFieldOrder(['openemis_no', 'student_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'student_behaviour_category_id']);
        }
    } */

    // POCOR 6154 
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no', ['visible' => true]);
        $this->field('description', ['visible' => false]);
        $this->field('action', ['visible' => false]);
        $this->field('time_of_behaviour', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);

        $this->fields['student_id']['sort'] = ['field' => 'Students.first_name']; // POCOR-2547 adding sort

        $this->setFieldOrder(['openemis_no', 'student_id', 'date_of_behaviour', 'title', 'student_behaviour_category_id']);
    }

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
    }
    // POCOR 6154 

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
        $this->setFieldOrder(['academic_period_id', 'class', 'student_id', 'student_behaviour_category_id', 'date_of_behaviour', 'time_of_behaviour']);
        // POCOR 6154 
    }

    // PHPOE-1916
    // public function viewAfterAction(Event $event, Entity $entity) {
    // 	$this->request->data[$this->alias()]['student_id'] = $entity->student_id;
    // 	$this->request->data[$this->alias()]['date_of_behaviour'] = $entity->date_of_behaviour;
    // }

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['AcademicPeriods','Students','StudentBehaviourCategories']);// POCOR 6154 
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('date_of_behaviour', ['entity' => $entity]);
        $this->fields['student_id']['attr']['value'] = $entity->student->name_with_id;

        // PHPOE-1916
        // Not yet implemented due to possible performance issue
        // $InstitutionClassStudentTable = TableRegistry::get('Institution.InstitutionClassStudents');
        // $AcademicPeriodId = $InstitutionClassStudentTable->find()
        // 				->where([$InstitutionClassStudentTable->aliasField('student_id') => $entity->student_id])
        // 				->innerJoin(['InstitutionClasses' => 'institution_classes'],[
        // 						'InstitutionClasses.id = '.$InstitutionClassStudentTable->aliasField('institution_class_id'),
        // 						'InstitutionClasses.institution_id' => $entity->institution_id
        // 					])
        // 				->innerJoin(['AcademicPeriods' => 'academic_periods'], [
        // 						'AcademicPeriods.id = InstitutionClasses.academic_period_id',
        // 						'AcademicPeriods.start_date <= ' => $entity->date_of_behaviour->format('Y-m-d'),
        // 						'AcademicPeriods.end_date >= ' => $entity->date_of_behaviour->format('Y-m-d')
        // 					])
        // 				->select(['id' => 'AcademicPeriods.id', 'editable' => 'AcademicPeriods.editable'])
        // 				->first()
        // 				->toArray();

        // if (! $AcademicPeriodId['editable']) {
        // 	$urlParams = $this->url('view');
        // 	$event->stopPropagation();
        // 	return $this->controller->redirect($urlParams);
        // }
    }

    // PHPOE-1916
    // Not yet implemented due to possible performance issue
    // public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
    // 	if ($action == 'view') {
    // 		$institutionId = $this->Session->read('Institution.Institutions.id');
    // 		$studentId = $this->request->data[$this->alias()]['student_id'];
    // 		$dateOfBehaviour = $this->request->data[$this->alias()]['date_of_behaviour'];
    // 		$InstitutionClassStudentTable = TableRegistry::get('Institution.InstitutionClassStudents');
    // 		$AcademicPeriodId = $InstitutionClassStudentTable->find()
    // 				->where([$InstitutionClassStudentTable->aliasField('student_id') => $studentId])
    // 				->innerJoin(['InstitutionClasses' => 'institution_classes'],[
    // 						'InstitutionClasses.id = '.$InstitutionClassStudentTable->aliasField('institution_class_id'),
    // 						'InstitutionClasses.institution_id' => $institutionId
    // 					])
    // 				->innerJoin(['AcademicPeriods' => 'academic_periods'], [
    // 						'AcademicPeriods.id = InstitutionClasses.academic_period_id',
    // 						'AcademicPeriods.start_date <= ' => $dateOfBehaviour->format('Y-m-d'),
    // 						'AcademicPeriods.end_date >= ' => $dateOfBehaviour->format('Y-m-d')
    // 					])
    // 				->select(['id' => 'AcademicPeriods.id', 'editable' => 'AcademicPeriods.editable'])
    // 				->first()
    // 				->toArray();

    // 		if (! $AcademicPeriodId['editable']) {
    // 			if(isset($toolbarButtons['edit'])) {
    // 				unset($toolbarButtons['edit']);
    // 			}
    // 		}
    // 	}
    // }
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
    }

    public function setupFields(Entity $entity, ArrayObject $extra)
    { 

        $this->field('infrastructure_need_type_id',['after' => 'name','visible' => ['view' => true,'edit' => true]]);
        $this->fields['priority']['default'] = $entity->priority;
        $this->field('priority',['after' => 'description','visible' => ['view' => true,'edit' => true]]);
        $this->field('file_name', ['type' => 'hidden']);
        $this->field('file_content', ['after' => 'date_completed','visible' => ['view' => false, 'edit' => true]]);

        // $this->setFieldOrder(['academic_period_id', 'date_of_visit', 'quality_visit_type_id', 'comment', 'file_name', 'file_content']);
    }

    /* pocor-6154 */
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['student_id']['type'] = 'select';
        $this->field('student_id', ['attr' => ['label' => __('Student')]]);

        $this->fields['student_behaviour_category_id']['type'] = 'select';
        $this->field('student_behaviour_category_id', ['attr' => ['label' => __('Student')]]);
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
    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->field('academic_period_id', ['visible' => false]);// POCOR 6154 
        $this->request->data[$this->alias()]['student_id'] = $entity->student_id;
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
            if (! $selectedClass==0	&& ! empty($selectedClass)) {
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
        ->select(['title' => 'StudentBehaviours.title','category_name' => 'StudentBehaviourCategories.name','date_of_behaviour' => 'StudentBehaviours.date_of_behaviour', 'openemis_no' => 'Students.openemis_no', 'student_name' => $User->find()->func()->concat([
            'first_name' => 'literal',
            " ",
            'last_name' => 'literal'
        ])])
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

}


