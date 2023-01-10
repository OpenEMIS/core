<?php
namespace Quality\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Cake\Chronos\Date;
use Cake\Chronos\Chronos;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;

class VisitRequestsTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
       
        $this->table('institution_visit_requests');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('QualityVisitTypes', ['className' => 'FieldOption.QualityVisitTypes', 'foreignKey' => 'quality_visit_type_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        $this->addBehavior('Quality.Visit');
        $this->addBehavior('Workflow.Workflow');
        // setting this up to be overridden in viewAfterAction(), this code is required
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
        $this->addBehavior('Excel', ['pages' => ['index']]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('date_of_visit', 'ruleDateWithinAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                'provider' => 'table',
            ])
            ->allowEmpty('file_content');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);

        $this->setFieldOrder(['academic_period_id', 'date_of_visit', 'quality_visit_type_id']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function () use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupFields($entity, $extra);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $academicPeriodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
            if ($entity->has('academic_period_id')) {
                $academicPeriodId = $entity->academic_period_id;
            } else {
                if (is_null($request->query('academic_period_id'))) {
                    $academicPeriodId = $this->AcademicPeriods->getCurrent();
                } else {
                    $academicPeriodId = $request->query('academic_period_id');
                }
                $entity->academic_period_id = $academicPeriodId;
            }

            $attr['select'] = false;
            $attr['options'] = $academicPeriodOptions;
            $attr['value'] = $academicPeriodId;
            $attr['attr']['value'] = $academicPeriodId;
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        }

        return $attr;
    }

    public function onUpdateFieldDateOfVisit(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            if ($entity->has('academic_period_id')) {
                $academicPeriodObj = $this->AcademicPeriods->get($entity->academic_period_id);

                $attr['date_options']['startDate'] = $academicPeriodObj->start_date->format('d-m-Y');
                $attr['date_options']['endDate'] = $academicPeriodObj->end_date->format('d-m-Y');
            }
        }

        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['academic_period_id']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['academic_period_id'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function setupFields(Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('date_of_visit', ['entity' => $entity]);
        $this->field('quality_visit_type_id', ['type' => 'select']);
        $this->field('file_name', ['type' => 'hidden']);
        $this->field('file_content', ['visible' => ['view' => false, 'edit' => true]]);

        $this->setFieldOrder(['academic_period_id', 'date_of_visit', 'quality_visit_type_id', 'comment', 'file_name', 'file_content']);
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('date_of_visit'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->AcademicPeriods->aliasField('name'),
                $this->QualityVisitTypes->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->AcademicPeriods->alias(), $this->QualityVisitTypes->alias(), $this->Institutions->alias(), $this->CreatedUser->alias(),'Assignees'])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId,
                'Assignees.super_admin IS NOT' => 1]) //POCOR-7102
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'VisitRequests',
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
                    $row['request_title'] = sprintf(__('%s in %s on %s'), $row->quality_visit_type->name, $row->academic_period->name, $this->formatDate($row->date_of_visit));
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    // POCOR-6166
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        // POCOR-6166
        $category = $this->request->query('category');
        // POCOR-6166
		$institutionId = $this->Session->read('Institution.Institutions.id');
        $assignees = TableRegistry::get('security_users');
		$query
		->select(['assignee' => $assignees->find()->func()->concat([
            'first_name' => 'literal',
            " ",
            'last_name' => 'literal'
        ]),
        'academic_period' => 'AcademicPeriods.name',
        'date_of_visit' => 'VisitRequests.date_of_visit',
        'quality_visit_type' => 'QualityVisitTypes.name'])

		->LeftJoin([$this->AcademicPeriods->alias() => $this->AcademicPeriods->table()],[
			$this->AcademicPeriods->aliasField('id').' = ' . 'VisitRequests.academic_period_id'
		])
        // POCOR-6166
		->LeftJoin([$this->Statuses->alias() => $this->Statuses->table()],[
            $this->Statuses->aliasField('id').' = ' . 'VisitRequests.status_id'
        ])
        // POCOR-6166
		->LeftJoin([$this->Assignees->alias() => $this->Assignees->table()],[
            $this->Assignees->aliasField('id').' = ' . 'VisitRequests.assignee_id'
        ]) 
        ->LeftJoin([$this->QualityVisitTypes->alias() => $this->QualityVisitTypes->table()],[
            $this->QualityVisitTypes->aliasField('id').' = ' . 'VisitRequests.quality_visit_type_id'
        ])
        ->where(['VisitRequests.institution_id' =>  $institutionId]);     
                
        // POCOR-6166
        if(isset($category) && $category > 0){
            $query
            ->where([
                $this->Statuses->aliasField('category') =>  $category
            ]);
        }
        // POCOR-6166
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        // POCOR-6166
        $extraField[] = [
            'key' => 'VisitRequests.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => __('Status')
        ];
        // POCOR-6166
        $extraField[] = [
            'key' => 'Assignees.assignee',
            'field' => 'assignee',
            'type' => 'string',
            'label' => __('Assignee')
        ];

        $extraField[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'integer',
            'label' => __('Academic Period')
        ];

        $extraField[] = [
            'key' => 'VisitRequests.date_of_visit',
            'field' => 'date_of_visit',
            'type' => 'date',
            'label' => __('Date Of Visit')
        ];

        $extraField[] = [
            'key' => 'QualityVisitTypes.name',
            'field' => 'quality_visit_type',
            'type' => 'string',
            'label' => __('Quality Visit Type')
        ];

        $fields->exchangeArray($extraField);
    }
    // POCOR-6166

    //POCOR-6925
    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Institutions > Visits > Requests';
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
}
