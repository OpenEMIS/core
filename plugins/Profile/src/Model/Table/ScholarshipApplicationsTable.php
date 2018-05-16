<?php
namespace Profile\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class ScholarshipApplicationsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $interestRateOptions = [];

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

        $this->interestRateOptions = $this->getSelectOptions('Scholarships.interest_rate');
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
            $this->controller->set('contentHeader', $scholarshipName . ' - ' . __('Overview'));

            // set tabs
            $tabElements = $this->ScholarshipTabs->getScholarshipProfileTabs();
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

            $scholarshipDetails = $this->Scholarships->get($scholarshipId, ['contain' => ['AcademicPeriods' , 'FinancialAssistanceTypes', 'Loans.PaymentFrequencies']]);
           
            $this->setupScholarshipFields($scholarshipDetails);
 
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

    public function onUpdateFieldFinancialAssistanceTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
           
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->scholarship_financial_assistance_type_id;
            $attr['attr']['value'] = $entity->financial_assistance_type->name;  
        }
        return $attr;
    }

    public function onUpdateFieldScholarshipId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->id;
            $attr['attr']['value'] = $entity->name;  

            $financialAssistanceTypeCode = $entity->financial_assistance_type->code;

            if ($financialAssistanceTypeCode == 'LOAN') {

                $this->field('requested_amount', [
                    'visible' => true,
                    'type' => 'integer',
                    'attr' => ['label' => __('Requested Amount')]
                ]);
                $this->field('interest_rate', [
                    'type' => 'disabled',
                    'attr' => [
                        'label' => __('Interest Rate') . ' (%)',
                        'value' => $entity->loan->interest_rate
                    ]
                ]);
                $this->field('interest_rate_type', [
                    'type' => 'disabled',
                    'attr' => [
                        'label' => __('Interest Rate Type'),
                        'value' => $this->interestRateOptions[$entity->loan->interest_rate_type]
                    ]
                ]);
                $this->field('payment_frequency_id', [
                    'type' => 'disabled',
                    'attr' => [
                        'label' => __('Payment Frequency'),
                        'value' => $entity->loan->payment_frequency->name
                    ]
                ]);
                $this->field('loan_term', [
                    'type' => 'disabled',
                    'attr' => [
                        'label' => __('Loan Term'),
                        'value' => $entity->loan->loan_term . ' ' . __('Years')
                    ]
                ]);
                
            }

        }
        return $attr;
    }


    public function setupScholarshipFields($entity = null)
    {
        $this->field('financial_assistance_type_id', ['entity' => $entity]);
        $this->field('scholarship_id', ['type' => 'string','entity' => $entity]);
        $this->field('academic_period_id', ['type' => 'disabled']);
        $this->field('description', ['type' => 'text', 'attr' => ['disabled' => 'disabled']]);
        $this->field('maximum_award_amount', [
            'type' => 'disabled',
            'attr' => ['label' => __('Maximum Award Amount')]
        ]);
        $this->field('bond', ['type' => 'disabled']);
        $this->field('requirements', ['type' => 'text', 'attr' => ['disabled' => 'disabled']]);

        if (!is_null($entity)) {
            $this->fields['academic_period_id']['attr']['value'] = $entity->has('academic_period') ? $entity->academic_period->name : '';
            $this->fields['description']['attr']['value'] = $entity->description;
            $this->fields['maximum_award_amount']['attr']['value'] = $entity->maximum_award_amount;
            $this->fields['bond']['attr']['value'] = $entity->bond . ' ' . __('Years');
            $this->fields['requirements']['attr']['value'] = $entity->requirements;
        }
    }
}
