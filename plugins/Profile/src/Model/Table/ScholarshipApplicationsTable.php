<?php
namespace Profile\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class ScholarshipApplicationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->hasMany('ApplicationInstitutionChoices', [
            'className' => 'Scholarship.ApplicationInstitutionChoices',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ApplicationAttachments', [
            'className' => 'Scholarship.ApplicationAttachments',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Workflow.Workflow', ['model' => 'Scholarship.Applications']);
        $this->addBehavior('CompositeKey');

        $this->toggle('edit', false);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $params = [
            'applicant_id' => $entity->applicant_id,
            'scholarship_id' => $entity->scholarship_id
        ];

        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = $this->ControllerAction->setQueryString($buttons['view']['url'], $params);
        }

        return $buttons;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if (in_array($this->action, ['view', 'edit'])) {
            // set header
            $scholarshipId = $this->getQueryString('scholarship_id');
            $scholarshipName = $this->Scholarships->get($scholarshipId)->name;
            $header = $scholarshipName . ' - ' . __('Overview');
            $this->controller->set('contentHeader', $header);

            // set tabs
            $tabElements = $this->controller->ScholarshipTabs->getScholarshipProfileTabs();
            $this->controller->set('tabElements', $tabElements);
            $this->controller->set('selectedAction', $this->alias());
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('requested_amount', ['visible' => false]);
        $this->field('academic_period_id');
        $this->field('scholarship_id', ['type' => 'string']);
        $this->field('financial_assistance_type_id');
        $this->setFieldOrder(['status_id', 'assignee_id', 'academic_period_id', 'scholarship_id', 'financial_assistance_type_id']);

        // scholarship directory add button
        if ($extra['toolbarButtons']->offsetExists('add')) {
            $extra['toolbarButtons']['add']['url'] = [
                'plugin' => 'Profile',
                'controller' => 'ScholarshipDirectories',
                'action' => 'index'
            ];
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Scholarships' => ['AcademicPeriods', 'FinancialAssistanceTypes']]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $queryString = $this->ControllerAction->getQueryString();

        if (array_key_exists('scholarship_id', $queryString) && !empty($queryString['scholarship_id'])) {
            $scholarshipId = $queryString['scholarship_id'];
            $applicantId = $this->Auth->user('id');

            $scholarshipDetails = $this->Scholarships->get($scholarshipId, ['contain' => ['FinancialAssistanceTypes']]);
            $financialAssistanceTypeCode = $scholarshipDetails->financial_assistance_type->code;

            if ($financialAssistanceTypeCode != 'LOAN') {
                $application = [];
                $application['applicant_id'] = $applicantId;
                $application['scholarship_id'] = $scholarshipId;
                $application['status_id'] = 0;
                $entity = $this->newEntity($application);

                if ($this->save($entity)) {
                    $this->Alert->success('general.add.success', ['reset' => true]);
                } else {
                    $this->Alert->error('general.add.failed', ['reset' => true]);
                }

                $event->stopPropagation();
                return $this->controller->redirect($this->url('index', false));

            } else {
                $this->field('scholarship_id', ['type' => 'readonly', 'value' => $scholarshipId, 'attr' => ['value' => $scholarshipDetails->code_name]]);
                $this->field('applicant_id', ['type' => 'hidden', 'value' => $applicantId]);
                $this->field('requested_amount', ['type' => 'float']);
            }
        } else {
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index', false));
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('requested_amount', ['visible' => false]);
        $this->field('academic_period_id');
        $this->field('code');
        $this->field('scholarship_id', ['type' => 'string']);
        $this->field('financial_assistance_type_id');
        $this->field('description', ['type' =>'text']);
        $this->field('maximum_award_amount');
        $this->field('bond');
        $this->field('requirements', ['type' =>'text']);
        $this->field('instructions', ['type' =>'text']);

        $this->setFieldOrder([
            'academic_period_id', 'code', 'scholarship_id', 'financial_assistance_type_id', 'description', 'maximum_award_amount', 'bond', 'requirements', 'instructions'
        ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Scholarships' => ['AcademicPeriods', 'FinancialAssistanceTypes']]);
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship') && $entity->scholarship->has('academic_period')) {
            $value = $entity->scholarship->academic_period->name;
        }
        return $value;
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->code;
        }
        return $value;
    }

    public function onGetDescription(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->description;
        }
        return $value;
    }

    public function onGetFinancialAssistanceTypeId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship') && $entity->scholarship->has('financial_assistance_type')) {
            $value = $entity->scholarship->financial_assistance_type->name;
        }
        return $value;
    }

    public function onGetMaximumAwardAmount(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->maximum_award_amount;
        }
        return $value;
    }

    public function onGetBond(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->bond;
        }
        return $value;
    }

    public function onGetRequirements(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->requirements;
        }
        return $value;
    }

    public function onGetInstructions(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->instructions;
        }
        return $value;
    }
}
