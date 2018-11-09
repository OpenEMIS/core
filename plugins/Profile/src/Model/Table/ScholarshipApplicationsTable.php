<?php
namespace Profile\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;
use Cake\Network\Request;


class ScholarshipApplicationsTable extends ControllerActionTable
{
    use OptionsTrait;

    CONST SCHOLARSHIP = 1;
    CONST LOAN = 2;

    private $interestRateOptions = [];
    private $currency = '';

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

        $this->interestRateOptions = $this->getSelectOptions('Scholarships.interest_rate');
        $this->currency = TableRegistry::get('Configuration.ConfigItems')->value('currency');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('requested_amount', [
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                    'message' => __('Value cannot be more than two decimal places')
                ],
                'ruleCheckRequestedAmount' => [
                    'rule' => ['checkRequestedAmount'],
                    'provider' => 'table',
                    'on' => function ($context) {
                        //trigger validation only when the application is of type 'LOAN'
                        return ($context['data']['financial_assistance_type_id'] == self::LOAN);
                    }
                ]
            ]);
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

        if (isset($buttons['edit']['url'])) {
            $buttons['edit']['url'] = $this->ControllerAction->setQueryString($buttons['edit']['url'], $params);
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
        $this->field('academic_period_id');
        $this->field('scholarship_id', ['type' => 'string']);
        $this->field('financial_assistance_type_id');
        $this->field('comments', ['visible' => false]);
        $this->field('requested_amount', ['visible' => false]);
        $this->setFieldOrder(['status_id', 'assignee_id', 'academic_period_id', 'scholarship_id', 'financial_assistance_type_id']);

        // scholarship directory add button
        if ($extra['toolbarButtons']->offsetExists('add')) {
            $extra['toolbarButtons']['add']['url'] = [
                'plugin' => 'Profile',
                'controller' => 'ScholarshipsDirectory',
                'action' => 'index'
            ];
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('applicant_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('requested_amount'),
                $this->aliasField('comments'),
                $this->aliasField('status_id'),
                $this->aliasField('assignee_id')
            ])
            ->contain([
                'Scholarships' => [
                    'fields' => [
                        'code',
                        'name',
                        'description',
                        'maximum_award_amount',
                        'bond',
                        'requirements',
                        'instructions',
                        'scholarship_financial_assistance_type_id',
                        'academic_period_id'
                    ]
                ],
                'Scholarships.AcademicPeriods' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Scholarships.FinancialAssistanceTypes' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Statuses' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Assignees' => [
                    'fields' => [
                        'id',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
                    ]
                ]
            ]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->isNew()) {
            $scholarshipId = $this->getQueryString('scholarship_id');
            $scholarshipEntity = $this->Scholarships->get($scholarshipId, ['contain' => [
                'AcademicPeriods',
                'FinancialAssistanceTypes',
                'Loans.PaymentFrequencies'
            ]]);
            $entity->scholarship_id = $scholarshipId;
            $entity->scholarship = $scholarshipEntity;
        }

        // POCOR-4836    
        $entity->applicant_id = $this->Auth->user('id');

        $applicantId = $this->ControllerAction->getQueryString('applicant_id');
        $applicantEntity = $this->Applicants->get($entity->applicant_id, ['contain' => ['Genders', 'MainIdentityTypes']]);
        $entity->applicant = $applicantEntity;

        $this->setupFields($entity);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('applicant_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('requested_amount'),
                $this->aliasField('comments'),
                $this->aliasField('status_id'),
                $this->aliasField('assignee_id')
            ])
            ->contain([
                'Scholarships' => [
                    'fields' => [
                        'code',
                        'name',
                        'description',
                        'maximum_award_amount',
                        'bond',
                        'requirements',
                        'instructions',
                        'scholarship_financial_assistance_type_id',
                        'academic_period_id'
                    ]
                ],
                'Scholarships.AcademicPeriods' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Scholarships.FinancialAssistanceTypes' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Scholarships.Loans' => [
                    'fields' => [
                        'interest_rate',
                        'interest_rate_type',
                        'loan_term',
                        'scholarship_payment_frequency_id'
                    ]
                ],
                'Scholarships.Loans.PaymentFrequencies' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Statuses' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Assignees' => [
                    'fields' => [
                        'id',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
                    ]
                ]
            ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'scholarship_id') {
            return __('Scholarship Name');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        return $entity->scholarship->academic_period->name;
    }

    public function onGetFinancialAssistanceTypeId(Event $event, Entity $entity)
    {
        return $entity->scholarship->financial_assistance_type->name;
    }

    public function onGetMaximumAwardAmount(Event $event, Entity $entity)
    {
        return $entity->scholarship->maximum_award_amount;
    }

    public function onGetBond(Event $event, Entity $entity)
    {
        return $entity->scholarship->bond . ' ' . __('Years');
    }

    public function onGetInterestRate(Event $event, Entity $entity)
    {
        if ($entity->has('scholarship') && $entity->scholarship->has('loan')) {
            return $entity->scholarship->loan->interest_rate;
        }
    }

    public function onGetInterestRateType(Event $event, Entity $entity)
    {
        if ($entity->has('scholarship') && $entity->scholarship->has('loan')) {
            $interestRateType = $entity->scholarship->loan->interest_rate_type;
            return $this->interestRateOptions[$interestRateType];
        }
    }

    public function onGetScholarshipPaymentFrequencyId(Event $event, Entity $entity)
    {
        if ($entity->has('scholarship') && $entity->scholarship->has('loan')) {
            return $entity->scholarship->loan->payment_frequency->name;
        }
    }

    public function onGetLoanTerm(Event $event, Entity $entity)
    {
        if ($entity->has('scholarship') && $entity->scholarship->has('loan')) {
            return $entity->scholarship->loan->loan_term . ' ' . __('Years');
        }
    }

    public function onUpdateFieldFinancialAssistanceTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $attr['value'] = $entity->scholarship->scholarship_financial_assistance_type_id;
            $attr['attr']['value'] = $entity->scholarship->financial_assistance_type->name;
        }
        return $attr;
    }

    public function onUpdateFieldScholarshipId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $attr['value'] = $entity->scholarship_id;
            $attr['attr']['value'] = $entity->scholarship->code_name;
        }
        return $attr;
    }

    public function onUpdateFieldBond(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $value = '';
            if (isset($entity->scholarship->bond) && strlen($entity->scholarship->bond) > 0) {
                $value = $entity->scholarship->bond . ' ' . __('Years');
            }
            $attr['attr']['value'] = $value;
        }
        return $attr;
    }

    public function onUpdateFieldInterestRateType(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $value = '';
            if (isset($entity->scholarship->loan->interest_rate_type) && strlen($entity->scholarship->loan->interest_rate_type) > 0) {
                $interestRateType = $entity->scholarship->loan->interest_rate_type;
                $value = $this->interestRateOptions[$interestRateType];
            }
            $attr['attr']['value'] = $value;
        }
        return $attr;
    }

    public function onUpdateFieldLoanTerm(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $value = '';
            if (isset($entity->scholarship->loan->loan_term) && strlen($entity->scholarship->loan->loan_term) > 0) {
                $value = $entity->scholarship->loan->loan_term . ' ' . __('Years');
            }
            $attr['attr']['value'] = $value;
        }
        return $attr;
    }

    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $displayValue = $entity->applicant->name_with_id;
            $value = $entity->applicant_id;

            $attr['value'] = $value;
            $attr['attr']['value'] = $displayValue;
            $attr['type'] = 'readonly';

            return $attr;
        }
    }

    public function setupFields($entity)
    {
        $isLoan = false;
        if ($entity->has('scholarship') && $entity->scholarship->has('scholarship_financial_assistance_type_id')) {
            $FinancialAssistanceTypesTable = TableRegistry::get('Scholarship.FinancialAssistanceTypes');
            $isLoan = $FinancialAssistanceTypesTable->is($entity->scholarship->scholarship_financial_assistance_type_id, 'LOAN');
        }

        $this->field('applicant_id', [
            'type' => 'hidden'
        ]);
        $this->field('financial_assistance_type_id', [
            'type' => 'readonly',
            'entity' => $entity
        ]);
        $this->field('scholarship_id', [
            'type' => 'readonly',
            'entity' => $entity
        ]);
         $this->field('academic_period_id', [
            'type' => 'disabled',
            'fieldName' => 'scholarship.academic_period.name'
        ]);
        $this->field('description', [
            'type' => 'text',
            'fieldName' => 'scholarship.description',
            'attr' => ['disabled' => 'disabled']
        ]);
        $this->field('requested_amount', [
            'type' => 'integer',
            'visible' => $isLoan,
            'attr' => ['label' => $this->addCurrencySuffix('Requested Amount')]
        ]);
        $this->field('maximum_award_amount', [
            'type' => 'disabled',
            'fieldName' => 'scholarship.maximum_award_amount',
            'attr' => ['label' => $this->addCurrencySuffix('Annual Award Amount')]
        ]);
        $this->field('bond', [
            'type' => 'disabled',
            'entity' => $entity
        ]);

        if ($isLoan) {
            $this->setupLoanFields($entity);
        }

        $this->field('requirements', [
            'type' => 'text',
            'fieldName' => 'scholarship.requirements',
            'attr' => ['disabled' => 'disabled']
        ]);
        $this->field('instructions', [
            'type' => 'text',
            'fieldName' => 'scholarship.instructions',
            'visible' => ['view' => true, 'add' => false, 'edit' => false]
        ]);
        $this->field('comments');
    }

    public function setupLoanFields($entity = null)
    {
        $this->field('interest_rate', [
            'type' => 'disabled',
            'fieldName' => 'scholarship.loan.interest_rate',
            'attr' => ['label' => __('Interest Rate').' (%)']
        ]);
        $this->field('interest_rate_type', [
            'type' => 'disabled',
            'entity' => $entity
        ]);
        $this->field('scholarship_payment_frequency_id', [
            'type' => 'disabled',
            'fieldName' => 'scholarship.loan.payment_frequency.name',
            'attr' => ['label' => __('Payment Frequency')]
        ]);
        $this->field('loan_term', [
            'type' => 'disabled',
            'entity' => $entity
        ]);
    }

    public function addCurrencySuffix($label)
    {
        return __($label) . ' (' . $this->currency . ')';
    }
}
