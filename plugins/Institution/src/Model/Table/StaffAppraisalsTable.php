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

class StaffAppraisalsTable extends ControllerActionTable
{    
    public $staff;

    public function initialize(array $config)
    {
        $this->table('institution_staff_appraisals');
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
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->allowEmpty('file_content')
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
            if (!is_null($this->request->query('user_id'))) {
                $userId = $this->request->query('user_id');
            } else {
                $session = $this->request->session();
                if ($session->check('Staff.Staff.id')) {
                    $userId = $session->read('Staff.Staff.id');
                }
            }

            if (!is_null($userId)) {
                $staff = $this->Users->get($userId);
                $this->staff = $staff;
                $this->controller->set('contentHeader', $staff->name. ' - ' .__('Appraisals'));
            }
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
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
        $session = $controller->request->session();

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
                $this->Users->alias(),
                $this->AppraisalTypes->alias(),
                $this->AppraisalForms->alias(),
                $this->AppraisalPeriods->alias(),
                $this->Institutions->alias(),
                $this->CreatedUser->alias()
            ])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([
                    $Statuses->aliasField('category <> ') => $doneStatus
                ]);
            })
            ->where([
                $this->aliasField('assignee_id') => $userId
            ])
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
                $AppraisalFormsCriteriasScores->aliasField('final_score') => 1
            ])
            ->innerJoin([$AppraisalFormsCriteriasScores->alias() => $AppraisalFormsCriteriasScores->table()], [
                $AppraisalFormsCriteriasScores->aliasField('appraisal_form_id = ') . $this->aliasField('appraisal_form_id'),
            ])
            ->innerJoin([$AppraisalScoreAnswers->alias() => $AppraisalScoreAnswers->table()], [
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
        $userId = $this->request->query('user_id');
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'StaffAppraisals');
    }
}
