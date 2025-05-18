<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Datasource\ResultSetInterface;
use Cake\I18n\Date;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;
use Page\Traits\EncodingTrait;
use App\Model\Traits\MessagesTrait;
use Cake\Http\ServerRequest;

class StaffBehavioursTable extends ControllerActionTable
{
    use OptionsTrait;
    use EncodingTrait;
    use MessagesTrait;
    // Workflow Steps - category
    const TO_DO = 1; //POCOR-6670
    const IN_PROGRESS = 2; //POCOR-6670
    const DONE = 3; //POCOR-6670
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('Staff', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffBehaviourCategories', ['className' => 'Staff.StaffBehaviourCategories']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);
        $this->hasMany('StaffBehaviourAttachments', [
            'className' => 'Institutions.StaffBehaviourAttachments',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']); //POCOR-6670
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);//POCOR-6670

        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Institution.Case');
        $this->addBehavior('Workflow.Workflow');//POCOR-6670
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index'],
        ]);
        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');

        $this->setDeleteStrategy('restrict');
        $roles = [1,2,3,4,5,6,7,8,9,10,11];
        $QueryResult = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions')->find()
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
        // if(!empty($QueryResult)){ //commented in POCOR-6155 
            $this->addBehavior('Excel', ['pages' => ['index']]);
        // }
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StaffBehaviours' =>['id']
            ]
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['InstitutionCase.onSetCustomCaseTitle'] = 'onSetCustomCaseTitle';
        $events['InstitutionCase.onSetCustomCaseSummary'] = 'onSetCustomCaseSummary';
        $events['InstitutionCase.onIncludeCustomExcelFields'] = 'onIncludeCustomExcelFields';
        $events['InstitutionCase.onBuildCustomQuery'] = 'onBuildCustomQuery';
        return $events;
    }

   /* public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('date_of_behaviour', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'provider' => 'table'
                ]
            ])
        ;
    }*/

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $event->getSubject()->Html->link($entity->staff->openemis_no, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StaffUser',
                'view',
                $this->paramsEncode(['id' => $entity->staff->id])
            ]);
        } else {
            return $entity->staff->openemis_no;
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no');
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('description', ['visible' => false]);
        $this->field('action', ['visible' => false]);
        $this->field('time_of_behaviour', ['visible' => false]);
        $this->field('behaviour_classification_id', ['attr' => ['label' => __('Classification')]]); // POCOR-7441
        $this->field('category_id', ['visible' => false]);//POCOR-6670
        $this->field('assignee_id', ['visible' => false]);//POCOR-6670
        $this->field('status_id', ['visible' => true]);//POCOR-6670

        $this->fields['staff_id']['sort'] = ['field' => 'Staff.first_name']; // POCOR-2547 adding sort

        $this->setFieldOrder(['openemis_no', 'staff_id', 'date_of_behaviour', 'staff_behaviour_category_id','behaviour_classification_id']);

        
        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Behaviour','Staff');       
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
        $this->setFieldOrder(['status','openemis_no', 'staff_id', 'date_of_behaviour', 'title', 'staff_behaviour_category_id']);//POCOR-6670
        $this->fields['assignee_id']['sort'] = ['field' => 'Assignees.first_name'];//POCOR-6670
    }


    // Start POCOR-7441
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('behaviour_classification_id', ['attr' => ['label' => __('Classification')]]);
        // $this->field('action', ['type' => 'text', 'after' => 'description','visible' => true]);
    }
    // End POCOR-7441

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['elements']['controls'] = ['name' => 'Institution.Behaviours/controls', 'data' => [], 'options' => [], 'order' => 1];
        $periodOptions = $this->AcademicPeriods->getYearList();
        $requestData = $this->request->getQuery('academic_period_id');
        if (empty($requestData)) {
            $academicPeriodId = $this->AcademicPeriods->getCurrent();
            $this->request = $this->request->withQueryParams(['academic_period_id' => $academicPeriodId]);
        }

        $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $institutionId = $this->getInstitutionID();
        $selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
            'callable' => function ($id) use ($Staff, $institutionId) {
                return $Staff
                    ->findByInstitutionId($institutionId)
                    ->find('academicPeriod', ['academic_period_id' => $id])
                    ->count();
            }
        ]);

        if (!empty($selectedPeriod)) {
            $query->find('inPeriod', ['field' => 'date_of_behaviour', 'academic_period_id' => $selectedPeriod]);
        }

        //$this->controller->set(compact('periodOptions'));

        // will need to check for search by name: AdvancedNameSearchBehavior

        // POCOR-2547 Adding sortWhiteList to $extra
        $sortList = ['Staff.first_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
        //POCOR-6670:start
        $query->contain(['Statuses']);
        if(!empty($this->request->getQuery('category_id')))
        {
            $query->where(['Statuses.category' => $this->request->getQuery('category_id') ]);
        }
        //POCOR-6670:end
        // POCOR-2547 sort list of staff and student by name
        if (($this->request->getQuery('sort') !='')) {
            $query->order([$this->Staff->aliasField('first_name'), $this->Staff->aliasField('last_name')]);
        }
        // end POCOR-2547
        //POCOR-6670:start
        if (!empty($selectedPeriod)) {
            $categories = ['0' => __('All Categories'),'1' => 'To Do','2'=>'In Progress','3'=>'Done'];
            $query->find('inPeriod', ['field' => 'date_of_behaviour', 'academic_period_id' => $selectedPeriod]);
        }
        $selectedCategories = $this->queryString('category_id', $categories);
        $this->advancedSelectOptions($categories, $selectedCategories);
        $this->controller->set(compact('periodOptions', 'categories'));
        //POCOR-6670:end
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('openemis_no', ['entity' => $entity]);
        $this->field('action', ['visible' => true]); // POCOR-7441

        $this->setFieldOrder(['academic_period_id', 'openemis_no', 'staff_id', 'staff_behaviour_category_id', 'behaviour_classification_id', 'date_of_behaviour', 'time_of_behaviour']);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('staff_id', ['entity' => $entity]);
        $this->field('date_of_behaviour', ['entity' => $entity]);
        $this->field('assignee_id', ['entity' => $entity]);
        $this->field('staff_behaviour_category_id', ['type' => 'select']);
        $this->field('behaviour_classification_id', ['type' => 'select']);
        $this->setFieldOrder(['academic_period_id', 'staff_id', 'staff_behaviour_category_id', 'behaviour_classification_id', 'date_of_behaviour', 'time_of_behaviour','description','action','assignee_id']);//POCOR-6670
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AcademicPeriods', 'Staff', 'StaffBehaviourCategories', 'BehaviourClassifications']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('staff_id', ['entity' => $entity]);
        $this->field('date_of_behaviour', ['entity' => $entity]);
        $this->field('staff_behaviour_category_id', ['entity' => $entity]);
        $this->field('behaviour_classification_id', ['entity' => $entity]);
        $this->fields['action']['attr']['value'] = $entity->action; // POCOR-7441

        $this->setFieldOrder(['academic_period_id', 'openemis_no', 'staff_id', 'staff_behaviour_category_id', 'behaviour_classification_id', 'date_of_behaviour', 'time_of_behaviour','description','action','assignee_id']);//POCOR-6670
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {   
        /*POCOR-starts*/
        list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

        if (!$isDeletable) {
            $this->Alert->warning('StaffBehaviours.restrictDelete');
            $urlParams = $this->url('index');
            $event->stopPropagation();
            return $this->controller->redirect($urlParams);
        }
        /*POCOR-ends*/

        $entity->showDeletedValueAs = $entity->description;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $entity = $attr['entity'];

            $academicPeriodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
            if ($entity->has('academic_period_id')) {
                $selectedPeriod = $entity->academic_period_id;
            } else {
                if (is_null($request->getQuery['academic_period_id'])) {
                    $selectedPeriod = $this->AcademicPeriods->getCurrent();
                } else {
                    $selectedPeriod = $request->getQuery['academic_period_id'];
                }
                $entity->academic_period_id = $selectedPeriod;
            }

            $attr['select'] = false;
            $attr['options'] = $academicPeriodOptions;
            $attr['value'] = $selectedPeriod;
            $attr['attr']['value'] = $selectedPeriod;
            $attr['onChangeReload'] = 'changePeriod';
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
        }

        return $attr;
    }

    public function onUpdateFieldDateOfBehaviour(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $selectedPeriod = $entity->academic_period_id;
            $academicPeriod = $this->AcademicPeriods->get($selectedPeriod);

            $startDate = $academicPeriod->start_date;
            $endDate = $academicPeriod->end_date;

            if ($action == 'add') {
                $todayDate = Date::now();

                if (!empty($request->data[$this->getAlias()]['date_of_behaviour'])) {
                    $inputDate = Date::createfromformat('d-m-Y', $request->data[$this->getAlias()]['date_of_behaviour']); //string to date object

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

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $staffOptions = [];

            $entity = $attr['entity'];
            $selectedPeriod = $entity->academic_period_id;

            if (!empty($selectedPeriod)) {
                $institutionId = $this->getInstitutionID();
                $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');
                $staffOptions = $Staff
                ->find('list', ['keyField' => 'staff_id', 'valueField' => 'staff_name'])
                ->matching('Users')
                ->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
                ->where([$Staff->aliasField('institution_id') => $institutionId])
                ->order(['Users.first_name', 'Users.last_name']) // POCOR-2547 sort list of staff and student by name
                ->toArray();
            }

            $attr['options'] = $staffOptions;
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->staff_id;
            $attr['attr']['value'] = $entity->staff->name_with_id;
        }
        return $attr;
    }

    public function onUpdateFieldStaffBehaviourCategoryId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->staff_behaviour_category_id;
            $attr['attr']['value'] = $entity->staff_behaviour_category->name;
        }

        return $attr;
    }

    public function onUpdateFieldBehaviourClassificationId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->behaviour_classification_id;
            $attr['attr']['value'] = $entity->behaviour_classification->name;
        }

        return $attr;
    }

    public function viewBeforeAction(Event $event)
    {
        $this->field('behaviour_classification_id', ['attr' => ['label' => __('Classification')]]); // POCOR-7441
        $tabElements = $this->getStaffBehaviourTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function onSetCustomCaseTitle(Event $event, Entity $entity)
    {
        $recordEntity = $this->get($entity->id, [
            'contain' => ['Staff', 'StaffBehaviourCategories', 'Institutions', 'BehaviourClassifications']
        ]);
        $title = '';
        $title .= $recordEntity->staff->name.' '.__('from').' '.$recordEntity->institution->code_name.' '.__('with').' '.$recordEntity->staff_behaviour_category->name;

        return [$title, true];
    }

    public function onSetCustomCaseSummary(Event $event, $id = null)
    {
        $recordEntity = $this->get($id, [
            'contain' => ['Staff', 'StaffBehaviourCategories', 'Institutions', 'BehaviourClassifications']
        ]);
        $summary = '';
        $summary .= $recordEntity->staff->name.' '.__('from').' '.$recordEntity->institution->code_name.' '.__('with').' '.$recordEntity->staff_behaviour_category->name;

        return $summary;
    }

    public function onIncludeCustomExcelFields(Event $event, $newFields)
    {
        $newFields[] = [
            'key' => 'Staff.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Staff.full_name',
            'field' => 'full_name',
            'type' => 'string',
            'label' => ''
        ];

        return $newFields;
    }

    public function onBuildCustomQuery(Event $event, $query)
    {
        $query
            ->select([
                'openemis_no' => 'Staff.openemis_no',
                'first_name' => 'Staff.first_name',
                'middle_name' =>'Staff.middle_name',
                'third_name' =>'Staff.third_name',
                'last_name' =>'Staff.last_name',
                'preferred_name' =>'Staff.preferred_name'

             ])
            ->innerJoinWith('InstitutionCaseRecords.StaffBehaviours.Staff');

        return $query;
    }

    public function getStaffBehaviourTabElements($options = [])
    {
        $tabElements = [];
        $institutionId = $this->getInstitutionID();
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $paramPass = $this->request->getParam('pass');
        $ids = isset($paramPass[1]) ? $this->paramsDecode($paramPass[1]) : [];
        $staffBehaviourId = $ids['id'];
        if(isset($ids['id'])) {
        $queryString = $this->paramsEncode(['staff_behaviour_id' => $staffBehaviourId,'institution_id'=> $institutionId]);

        $tabElements = [
            'StaffBehaviours' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffBehaviours', 'view', $paramPass[1]],
                'text' => __('Overview')
            ],
            'StaffBehaviourAttachments' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffBehaviourAttachments', 'index', $queryString],
                'text' => __('Attachments')
            ]
        ];}
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
            'label' => __('Staff')
        ];

        $extraField[] = [
            'key' => 'StudentBehaviour.date_of_behaviour',
            'field' => 'date_of_behaviour',
            'type' => 'date',
            'label' => __('Date Of Behaviour')
        ];

        $extraField[] = [
            'key' => 'StaffBehaviourCategories.name',
            'field' => 'category',
            'type' => 'string',
            'label' => __('Category')
        ];

        $extraField[] = [
            'key' => 'BehaviourClassifications.name',
            'field' => 'behaviour_classification',
            'type' => 'string',
            'label' => __('Behaviour Classification')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'linked_cases',
            'type' => 'integer',
            'label' => __('Linked Cases')
        ];
        // POCOR-6155
        $fields->exchangeArray($extraField);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        // POCOR-6155
        $academicPeriod = ($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent() ;
        // POCOR-6155
        $institutionId = $this->getInstitutionID();
        $User = TableRegistry::getTableLocator()->get('User.Users');
        $query
        ->select([
            'date_of_behaviour' => 'StaffBehaviours.date_of_behaviour',
            'category' => 'StaffBehaviourCategories.name',
            'behaviour_classification' => 'BehaviourClassifications.name', 
            'openemis_no' => 'Staff.openemis_no', 
            'student_name' => $User->find()->func()->concat([
                'first_name' => 'literal',
                " ",
                'last_name' => 'literal'
            ])
        ])
        ->LeftJoin([$this->Staff->getAlias() => $this->Staff->getTable()],[
            $this->Staff->aliasField('id').' = ' . 'StaffBehaviours.staff_id'
        ])
        ->LeftJoin([$this->StaffBehaviourCategories->getAlias() => $this->StaffBehaviourCategories->getTable()],[
            $this->StaffBehaviourCategories->aliasField('id').' = ' . 'StaffBehaviours.staff_behaviour_category_id'
        ])
        ->LeftJoin([$this->BehaviourClassifications->getAlias() => $this->BehaviourClassifications->getTable()],[
            $this->BehaviourClassifications->aliasField('id').' = ' . 'StaffBehaviours.behaviour_classification_id'
        ])
        ->where([
            'StaffBehaviours.academic_period_id' =>  $academicPeriod,
            'StaffBehaviours.institution_id' =>  $institutionId
        ]);

        // POCOR-6155 start
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                // POCOR-6155 linked cases from caseBehaviour
                $WorkflowRules = TableRegistry::getTableLocator()->get('Workflow.WorkflowRules');
                $InstitutionCases = TableRegistry::getTableLocator()->get('Cases.InstitutionCases');

                $feature = $WorkflowRules->getFeatureByEntity($row);
                $recordId = $row->id;
                $query = $InstitutionCases
                    ->find()
                    ->contain(['Statuses', 'Assignees'])
                    ->matching('LinkedRecords', function ($q) use ($feature, $recordId) {
                        return $q->where([
                            'feature' => $feature,
                            'record_id' => $recordId
                        ]);
                    });
                
                $linked_cases = $query->count();
                $row['linked_cases'] = $linked_cases;
                // POCOR-6155 linked cases
                return $row;
            });
        });
        // POCOR-6155 ends
    }

    /**
     * POCOR-6670 Assignee id
    */
    public function onGetAssigneeId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->assignee->name;
        } 
    }

    /**
     * POCOR-6670 Assignee id
     *add assignee dropdown in edit and view page
    */
    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Institutions > Behaviour > Staff';
            $workflowModelsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
            $workflowStepsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
            $Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
            $workModelId = $Workflows
                            ->find()
                            ->select(['id'=>$workflowModelsTable->aliasField('id'),
                            'workflow_id'=>$Workflows->aliasField('id'),
                            'is_school_based'=>$workflowModelsTable->aliasField('is_school_based')])
                            ->LeftJoin([$workflowModelsTable->getAlias() => $workflowModelsTable->getTable()],
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
            $session = $request->getSession();
            $institutionId = $this->getInstitutionID();
            
            $institutionId = $institutionId;
            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = TableRegistry::getTableLocator()->get('Workflow.WorkflowStepsRoles');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
                    $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                    $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
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


    /*POCOR-5177 starts*/
    private function checkIfCanEditOrDelete($entity) {
        $isEditable = true;
        $isDeletable = true;

        $ConfigItemsTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $compareDate = $ConfigItemsTable->find()
                        ->select([$ConfigItemsTable->aliasField('value')])
                        ->where([
                            $ConfigItemsTable->aliasField('name') => 'Staff Behavior',
                            $ConfigItemsTable->aliasField('code') => 'staff_behavior',
                            $ConfigItemsTable->aliasField('label') => 'Staff Behavior'
                        ])->first();
        if (!empty($compareDate) && $compareDate->value != 0) {
            $addDays = $compareDate->value;
            $getRecord = $this->find()
                            ->select([$this->aliasField('date_of_behaviour')])
                            ->where([$this->aliasField('id') => $entity->id])
                            ->first();
            $date = date('Y-m-d', strtotime($getRecord->date_of_behaviour));
            $newDate = date('Y-m-d', strtotime($date. ' + '. $addDays .' days'));
            $today = new Date();
            $todayDate = date('Y-m-d', strtotime($today));
            if ($newDate > $todayDate) {
                $isDeletable = false;
            }
        }

        return compact('isEditable', 'isDeletable');
    }

    /**
     * @param Query $query
     * @param array $options
     * @return Query
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->getRequest()->getSession();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Staff->aliasField('openemis_no'),
                $this->Staff->aliasField('first_name'),
                $this->Staff->aliasField('middle_name'),
                $this->Staff->aliasField('third_name'),
                $this->Staff->aliasField('last_name'),
                $this->Staff->aliasField('preferred_name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Staff->getAlias(), $this->Institutions->getAlias(), $this->CreatedUser->getAlias(),'Assignees'])
            ->matching($this->Statuses->getAlias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId,
                'Assignees.super_admin IS NOT'=> 1 ]) //POCOR-7102
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffBehaviours',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }
                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('Behavour request of %s'), $row->staff->name_with_id);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }


    /*public function deleteBeforeAction(Event $event, ArrayObject $extra)
    {   
        $id = $this->request->data['primaryKey'];
        $jsonData = base64_decode($id);
        preg_match_all('/{(.*?)}/', $jsonData, $matches);
        $requestData = json_decode($matches[0][0]);
        $ConfigItemsTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
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
                            ->select([$this->aliasField('created')])
                            ->where([$this->aliasField('id') => $requestData->id])
                            ->first();
            $date = date('Y-m-d', strtotime($getRecord->created));
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
    }*/
    /*POCOR-5177 ends*/

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
            case 'behaviour_classification_id':
                return __('Behaviour Classification');
            case 'assignee_id':
                return __('Assignee');
            case 'academic_period_id':
                return __('Academic Period');
            case 'description':
                return __('Description');
            case 'action':
                return __('Action');
            case 'status_id':
                return __('Status');
            case 'staff_id':
                return __('Staff');
            case 'linked_cases':   
                return __('Linked Cases');
            case 'created':
                return __('Created');
            case 'created_user_id':
                    return __('Created By');
            case 'modified':   
                return __('Modified');
            case 'modified_user_id':  
                return __('Modified By');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
