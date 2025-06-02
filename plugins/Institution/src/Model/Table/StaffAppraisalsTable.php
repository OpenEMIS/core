<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Datasource\ResultSetInterface;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\I18n\FrozenTime;
use Cake\I18n\FrozenDate;

class StaffAppraisalsTable extends ControllerActionTable
{
    public $staff;

    public function initialize( $config): void
    {
        $this->setTable('institution_staff_appraisals');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
        $this->belongsTo('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods']);
        $this->hasMany('AppraisalTextAnswers', [
            'className' => 'StaffAppraisal.AppraisalTextAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            // 'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalSliderAnswers', [
            'className' => 'StaffAppraisal.AppraisalSliderAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            // 'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalDropdownAnswers', [
            'className' => 'StaffAppraisal.AppraisalDropdownAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            // 'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalNumberAnswers', [
            'className' => 'StaffAppraisal.AppraisalNumberAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            // 'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalScoreAnswers', [
            'className' => 'StaffAppraisal.AppraisalScoreAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        // for file upload
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.Appraisal');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('Institution.StaffProfile'); // POCOR-4047 to get staff profile data
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        $this->behaviors()->get('ControllerAction')->getConfig(
            'actions.download.show',
            true
        );

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StaffAppraisals' =>
                ['id','staff_id', 'institution_id']
            ]
        ]);
        $this->addBehavior('Staff.StaffTab');
        //POCOR-8627 Start
        $this->addBehavior('Excel',[
            'excludes' => ['security_user_id'],
            'pages' => ['view'],
        ]);
        //POCOR-8627 END
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->setProvider('custom', $this);
        return $validator
            ->allowEmpty('file_content')
            ->requirePresence('appraisal_period_id','create') // POCOR-9123
            ->requirePresence('appraisal_period_id','edit') // POCOR-9123
            ->requirePresence('appraisal_type_id','create') // POCOR-9123
            ->requirePresence('appraisal_type_id','edit') // POCOR-9123
            ->requirePresence('appraisal_form_id','create') // POCOR-9123
            ->requirePresence('appraisal_form_id','edit') // POCOR-9123
            ->add('appraisal_period_from', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'message' => __('Date range is not within the academic period.')
                ]
            ])
            ->add('appraisal_period_to', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'message' => __('Date range is not within the academic period.')
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'appraisal_period_from', true],
                    'message' => __('Appraisal Period To Date should not be earlier than Appraisal Period From Date')
                ]
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if (in_array($this->action, ['view', 'edit', 'delete'])) {
            $modelAlias = 'Staff Appraisals';
            $userType = 'StaffUser';
            $this->controller->changeUserHeader($this, $modelAlias, $userType);
        }

        if ($this->action != 'download') {
            if (!is_null($this->request->getQuery('user_id'))) {
                $userId = $this->request->getQuery('user_id');
            } else {
                // $userId = $this->getStaffId();
                // echo "<pre>"; print_r('$userId');
                // echo "<pre>"; print_r($userId);
                // die;
                $session = $this->request->getSession();
                if ($session->check('Staff.Staff.id')) {
                    $userId = $session->read('Staff.Staff.id');
                }
            }
            if(empty($userId) && isset($this->request->getParam('pass')[1])) {
                $userId =  $this->ControllerAction->paramsDecode($this->request->getParam('pass')[1])['staff_id'];
            }

            if (!is_null($userId)) {
                $staff = $this->Users->get($userId);
                $this->staff = $staff;
                $this->controller->set('contentHeader', $staff->name. ' - ' .__('Appraisals'));
            }
        }
        $institutionId = $this->getInstitutionID(); // POCOR-8688
        $this->field('institution_id', ['type' => 'hidden', 'value' => $institutionId]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Appraisals','Staff - Career');
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
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('staff_id') => $this->staff->id]);
        $this->field('final_score');
    }

    public function afterSaveCommit(Event $event, Entity $entity, ArrayObject $options)
    {
        $broadcaster = $this;
        $listeners = [];
        $listeners[] = $this->AppraisalForms->AppraisalFormsCriteriasScores;

        $this->dispatchEventToModels('Model.InstitutionStaffAppraisal.addAfterSave', [$entity], $broadcaster, $listeners);
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->getRequest()->getSession();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = WorkflowSteps::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('staff_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name'),
                $this->AppraisalTypes->aliasField('name'),
                $this->AppraisalForms->aliasField('name'),
                $this->AppraisalPeriods->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([
                $this->Users->getAlias(),
                $this->AppraisalTypes->getAlias(),
                $this->AppraisalForms->getAlias(),
                $this->AppraisalPeriods->getAlias(),
                $this->Institutions->getAlias(),
                $this->CreatedUser->getAlias(),
                'Assignees'
            ])
            ->matching($this->Statuses->getAlias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([
                    $Statuses->aliasField('category <> ') => $doneStatus
                ]);
            })
            ->where([
                $this->aliasField('assignee_id') => $userId,
                'Assignees.super_admin IS NOT' => 1]) //POCOR-7102
            ->order([
                $this->aliasField('created') => 'DESC'
            ])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffAppraisals',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'user_id' => $row->staff_id,
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                     } else {
                        $receivedDate = $this->formatDate($row->modified);
                     }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    // Name (Type) for OpenEMIS ID - Staff Name in Appraisal Period
                    $row['request_title'] = sprintf(__('%s (%s) for %s in %s'), $row->appraisal_form->name, $row->appraisal_type->name, $row->user->name_with_id, $row->appraisal_period->name);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    public function onGetFinalScore(Event $event, Entity $entity)
    {
        $institutionStaffAppraisalsId = $entity->id;
        $AppraisalFormsCriteriasScores = $this->AppraisalForms->AppraisalFormsCriteriasScores;
        $AppraisalScoreAnswers = $this->AppraisalScoreAnswers;

        $results = $this->find()
            ->select([
                'answer' => $AppraisalScoreAnswers->aliasField('answer')
            ])
            ->where([
                $this->aliasField('id') => $institutionStaffAppraisalsId,
                $AppraisalFormsCriteriasScores->aliasField('final_score IS NOT NULL') // POCOR-9123
            ])
            ->innerJoin([$AppraisalFormsCriteriasScores->getAlias() => $AppraisalFormsCriteriasScores->getTable()], [
                $AppraisalFormsCriteriasScores->aliasField('appraisal_form_id = ') . $this->aliasField('appraisal_form_id'),
            ])
            ->innerJoin([$AppraisalScoreAnswers->getAlias() => $AppraisalScoreAnswers->getTable()], [
                $AppraisalScoreAnswers->aliasField('appraisal_form_id = ') . $AppraisalFormsCriteriasScores->aliasField('appraisal_form_id'),
                $AppraisalScoreAnswers->aliasField('appraisal_criteria_id = ') . $AppraisalFormsCriteriasScores->aliasField('appraisal_criteria_id'),
                $AppraisalScoreAnswers->aliasField('institution_staff_appraisal_id = ') . $institutionStaffAppraisalsId
            ])
            ->all();

        $answer = "<i class='fa fa-minus'></i>";
        if (!$results->isEmpty()) {
            $resultEntity = $results->first();
            if ($resultEntity->has('answer') && !is_null($resultEntity->answer)) {
                $answer = $resultEntity->answer. ' ';
            }
        }
        return $answer;
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->request->getQuery('user_id');
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        //$tabElements = $this->controller->getCareerTabElements($options);
        $tabElements = $this->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'StaffAppraisals');
    }


    //POCOR-6925
    public function onUpdateFieldAssigneeId(Event $event, $attr, $action, ServerRequest $request) // POCOR-9123
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Staff > Career > Appraisals';
            $workflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');
            $workflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
            $Workflows = TableRegistry::get('Workflow.Workflows');
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
            // $session = $request->getSession();
            // if ($session->check('Institution.Institutions.id')) {
            //     $institutionId = $session->read('Institution.Institutions.id');
            // }
            $institutionId = $this->getInstitutionID();
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
           // $attr['onChangeReload'] = 'changeStatus'; //POCOR-8688
            return $attr;
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'appraisal_period_id') {
            return __('Appraisal Period');
        } elseif ($field == 'appraisal_form_id') {
            return __('Appraisal Form');
        } elseif ($field == 'appraisal_period_from') {
            return __('Appraisal Period From');
        } elseif ($field == 'appraisal_period_to') {
            return __('Appraisal Period To');
        } elseif ($field == 'date_appraised') {
            return __('Date Appraised');
        } elseif ($field == 'file_content') {
            return __('Attachment');
        } elseif ($field == 'comment') {
            return __('Comment');
        } elseif ($field == 'assignee_id') {
            return __('Assignee');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-8627 Start
    public function onExcelRenderDate(Event $event, Entity $entity, $attr)
    {
        $field = $entity->{$attr['field']};

        if (!empty($field)) {
            if ($field instanceof FrozenTime || $field instanceof FrozenDate) {
                return $this->formatDate($field);
            } else {
                $date = new FrozenTime($field);
                return $this->formatDate($date);
            }
        } else {
            return $field;
        }

    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $query->contain([
            'AppraisalPeriods.AcademicPeriods', 'AppraisalForms',
            'AppraisalTypes'
        ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $fields[] = [
            'key' => 'AppraisalPeriods.AcademicPeriods.AcademicPeriods',
            'field' => 'code',
            'type' => 'string',
            'label' => 'Academic Period',
        ];
    }

    public function onExcelGetCode(Event $event, Entity $entity)
    {
       return $entity->appraisal_period->academic_period->name;
    }
    //POCOR-8627 End
}
