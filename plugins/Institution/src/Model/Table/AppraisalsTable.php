<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Chronos\Date;
use Cake\Chronos\Chronos;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;

use App\Model\Table\ControllerActionTable;

class AppraisalsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_staff_appraisals');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
        $this->belongsTo('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->hasMany('AppraisalTextAnswers', ['className' => 'StaffAppraisal.AppraisalTextAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalSliderAnswers', ['className' => 'StaffAppraisal.AppraisalSliderAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalDropdownAnswers', ['className' => 'StaffAppraisal.AppraisalDropdownAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalNumberAnswers', ['className' => 'StaffAppraisal.AppraisalNumberAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalScoreAnswers', ['className' => 'StaffAppraisal.AppraisalScoreAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('Institution.Appraisal');
        $this->addBehavior('Workflow.Workflow', [
            'model' => 'Institution.StaffAppraisals',
            'actions' => [
                'add' => false,
                'remove' => false,
                'edit' => false
            ],
            'disableWorkflow' => true,
            'filter' => [
                'type' => true,
                'category' => false
            ]
        ]);

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

   public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {   
        $doneStatus = WorkflowSteps::DONE;
        $Statuses = $this->Statuses;
        $query
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category') => $doneStatus]);
            });
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->Auth->user('id');
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('institution_id');
        $this->setFieldOrder(['academic_period_id', 'appraisal_type_id', 'appraisal_period_id', 'appraisal_form_id', 'appraisal_period_from', 'appraisal_period_to', 'date_appraised', 'file_content', 'comment']);
    }

}
