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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('staff_id') => $this->staff->id]);
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        // $appraisalScoreAnswers = $this->AppraisalScoreAnswers;
        
        // // Dispatch this event method to the AppraisalScoreAnswersTable to do the saving of records den pass back to this table so that it will be become low coupling.
        // // Why dispatch event behind need the table info???
        // $appraisalScoreAnswers->dispatchEvent('Model.Appraisal.edit.beforePatch', [$requestData, $this->alias()], $appraisalScoreAnswers);
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOption, ArrayObject $extra)
    {
        $appraisalScoreAnswers = $this->AppraisalScoreAnswers;

        // Dispatch this event method to the AppraisalScoreAnswersTable to do the saving of records den pass back to this table so that it will be become low coupling.
        // Why dispatch event behind need the table info???
        // $appraisalScoreAnswers->dispatchEvent('Model.Appraisal.edit.beforePatch', [$requestData, $this->alias()], $appraisalScoreAnswers);

        // pr($requestData[$this->alias()]);die;
        // pr($requestData);die;

    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $appraisalScore = $this->AppraisalForms->AppraisalFormsCriteriasScores;
        $action = 'calculateScoreAfterSliderCriteriaIsSaved';
        // All the slider criteria has been save to DB already, when it come until here therefore now "retrieve" all the question from DB and all the "SCORE" type and calculate then save back to db for the score fields.
        $appraisalScore->dispatchEvent('Model.Appraisal.edit.afterSave', [$entity, $action], $appraisalScore);
        // pr($entity);die;
    }
}
