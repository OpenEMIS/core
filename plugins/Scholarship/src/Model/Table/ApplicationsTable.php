<?php
namespace Scholarship\Model\Table;

use ArrayObject;
use DateTime;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Controller\Component;
use Cake\Datasource\ResultSetInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;

class ApplicationsTable extends ControllerActionTable
{
    use OptionsTrait;

    CONST SCHOLARSHIP = 1;
    CONST LOAN = 2;

    private $interestRateOptions = [];
    private $currency = [];
    private $workflowEvents = [
        [
            'value' => 'Workflow.onApproveScholarship',
            'text' => 'Approval of Scholaship Application',
            'description' => 'Performing this action will add the applicant as a scholarship recipient.',
            'method' => 'onApproveScholarship',
            'unique' => true
        ],
        [
            'value' => 'Workflow.onWithdrawScholarship',
            'text' => 'Withdrawal from Scholarship Applications',
            'description' => 'Performing this action will withdraw the applicant from the approved scholarship applications.',
            'method' => 'onWithdrawScholarship',
            'unique' => true
        ]
    ];

    public function initialize(array $config)
    {
        $this->table('scholarship_applications');
        parent::initialize($config);

        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->hasMany('ApplicationAttachments', [
            'className' => 'Scholarship.ApplicationAttachments',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ApplicationInstitutionChoices', [
            'className' => 'Scholarship.ApplicationInstitutionChoices',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('CompositeKey');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        $this->interestRateOptions = $this->getSelectOptions('Scholarships.interest_rate');
        $this->currency = TableRegistry::get('Configuration.ConfigItems')->value('currency');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        foreach($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('financial_assistance_type_id', 'create')
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

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $title = __($this->getHeader($this->alias()));

        if (in_array($this->action, ['view', 'edit'])) {
            $applicantId = $this->ControllerAction->getQueryString('applicant_id');
            $applicantName = $this->Applicants->get($applicantId)->name;

            $Navigation->addCrumb($title, ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Applications', 'index']);
            $Navigation->addCrumb($applicantName);
            $Navigation->addCrumb(__('Overview'));
        } else {
            $Navigation->addCrumb($title);
        }
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'scholarship_id';
        $searchableFields[] = 'openemis_no';
        $searchableFields[] = 'applicant_id';
        $searchableFields[] = 'identity_number';
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if (in_array($this->action, ['view', 'edit'])) {
            // set header
            $applicantId = $this->ControllerAction->getQueryString('applicant_id');
            $applicantName = $this->Applicants->get($applicantId)->name;
            $this->controller->set('contentHeader', $applicantName . ' - ' . __('Overview'));

            // set tabs
            $tabElements = $this->ScholarshipTabs->getScholarshipApplicationTabs();
            $this->controller->set('tabElements', $tabElements);
            $this->controller->set('selectedAction', $this->alias());
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add']['url'])) {
            $extra['toolbarButtons']['add']['url']['controller'] = 'UsersDirectory';
            $extra['toolbarButtons']['add']['url']['action'] = 'index';
            $extra['toolbarButtons']['add']['attr']['title'] = __('Apply');
            unset($extra['toolbarButtons']['add']['url'][0]);
        }

        // setup fields
        $this->field('scholarship_id', ['type' => 'integer']);
        $this->field('openemis_no');
        $this->field('applicant_id', ['type' => 'integer']);
        $this->field('date_of_birth');
        $this->field('gender');
        $this->field('identity_type');
        $this->field('identity_number');
        $this->field('requested_amount', ['visible' => false]);
        $this->field('comments', ['visible' => false]);

        $this->fields['assignee_id']['sort'] = ['field' => 'Assignees.first_name'];
        $this->fields['scholarship_id']['sort'] = ['field' => 'Scholarships.name'];
        $this->fields['openemis_no']['sort'] = ['field' => 'Applicants.openemis_no'];
        $this->fields['applicant_id']['sort'] = ['field' => 'Applicants.first_name'];
        $this->fields['date_of_birth']['sort'] = ['field' => 'Applicants.date_of_birth'];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('applicant_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('requested_amount'),
                $this->aliasField('status_id'),
                $this->aliasField('assignee_id')
            ])
            ->contain([
                'Applicants' => [
                    'fields' => [
                        'id',
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name',
                        'gender_id',
                        'date_of_birth',
                        'identity_type_id',
                        'identity_number',
                    ]
                ],
                'Applicants.Genders' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Applicants.MainIdentityTypes' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Scholarships' => [
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

        // auto_contain_fields
        $extra['auto_contain_fields'] = ['Scholarships' => ['code']];

        // sort
        $sortList = ['Assignees.first_name', 'Scholarships.name', 'Applicants.openemis_no', 'Applicants.first_name', 'Applicants.date_of_birth'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $this->getNameSearchConditions(['alias' => $this->Applicants->alias(), 'searchTerm' => $search]);

            $searchString = $search . '%';
            $orConditions = [
                $this->Scholarships->aliasField('code LIKE') => $searchString,
                $this->Scholarships->aliasField('name LIKE') => $searchString,
                $this->Applicants->aliasField('identity_number LIKE') => $searchString
            ];

            $extra['OR'] = array_merge($nameConditions, $orConditions); // to be merged with auto_search 'OR' conditions
        }
    }

    public function addEditOnChangeFinancialAssistanceType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $data[$this->alias()]['scholarship_id'] = '';
        $data['scholarship'] = [];

        // Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'Applicants' => [
                'validate' => false
            ],
            'Scholarships' => [
                'validate' => false
            ]
        ];
    }

    public function addEditOnChangeScholarship(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $data['scholarship'] = [];

        // Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'Applicants' => [
                'validate' => false
            ],
            'Scholarships' => [
                'validate' => false
            ]
        ];
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        // remove queryString when redirect
        if (isset($extra['redirect']['queryString'])) {
            unset($extra['redirect']['queryString']);
        }

        if (isset($extra['toolbarButtons']['back']['url'])) {
            $extra['toolbarButtons']['back']['url'] = [
                'plugin' => 'Scholarship',
                'controller' => 'UsersDirectory',
                'action' => 'index'
            ];
        }
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

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->isNew()) {
            $entity->unsetProperty('scholarship');

            $applicantId = $this->ControllerAction->getQueryString('applicant_id');
            $applicantEntity = $this->Applicants->get($applicantId, ['contain' => ['Genders', 'MainIdentityTypes']]);

            $entity->applicant_id = $applicantEntity->id;
            $entity->applicant = $applicantEntity;

            if ($entity->has('scholarship_id')) {
                $scholarshipId = $entity->scholarship_id;
                $scholarshipEntity = $this->Scholarships->get($scholarshipId, [
                    'contain' => ['AcademicPeriods' , 'FinancialAssistanceTypes', 'Loans.PaymentFrequencies']
                ]);

                $entity->scholarship = $scholarshipEntity;
            }
        }

        // setup fields
        $this->setupApplicantFields($entity);
        $this->field('scholarship_details_header', ['type' => 'section', 'title' => __('Apply for Scholarship')]);
        $this->setupScholarshipFields($entity);
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        // remove queryString for index page
        if (isset($extra['toolbarButtons']['list']['url']['queryString'])) {
            unset($extra['toolbarButtons']['list']['url']['queryString']);
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        // remove queryString for index page
        if (isset($extra['toolbarButtons']['back']['url']['queryString'])) {
            unset($extra['toolbarButtons']['back']['url']['queryString']);
        }
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
                'Applicants' => [
                    'fields' => [
                        'id',
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name',
                        'gender_id',
                        'date_of_birth',
                        'identity_type_id',
                        'identity_number',
                    ]
                ],
                'Applicants.Genders' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Applicants.MainIdentityTypes' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'code',
                        'name',
                        'description',
                        'maximum_award_amount',
                        'total_amount',
                        'duration',
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
        $this->setupScholarshipFields($entity);
    }

    public function deleteBeforeAction(Event $event, ArrayObject $extra)
    {
        // remove queryString when redirect
        if (isset($extra['redirect']['queryString'])) {
            unset($extra['redirect']['queryString']);
        }
    }

    // index fields
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'scholarship_id') {
            return __('Scholarship Name');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    
    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->applicant->openemis_no;
    }

    public function onGetDateOfBirth(Event $event, Entity $entity)
    {
        return $this->formatDate($entity->applicant->date_of_birth);
    }

    public function onGetGender(Event $event, Entity $entity)
    {
        return $entity->applicant->gender->name;
    }

    public function onGetIdentityType(Event $event, Entity $entity)
    {
        if ($entity->has('applicant') && $entity->applicant->has('main_identity_type')) {
            return $entity->applicant->main_identity_type->name;
        }
    }

    public function onGetIdentityNumber(Event $event, Entity $entity)
    {
        return $entity->applicant->identity_number;
    }

    // view fields
    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        return $entity->scholarship->academic_period->name;
    }

    public function onGetFinancialAssistanceTypeId(Event $event, Entity $entity)
    {
        return $entity->scholarship->financial_assistance_type->name;
    }

    public function onGetDescription(Event $event, Entity $entity)
    {
        return $entity->scholarship->description;
    }

    public function onGetMaximumAwardAmount(Event $event, Entity $entity)
    {
        return $entity->scholarship->maximum_award_amount;
    }

    public function onGetDuration(Event $event, Entity $entity)
    {
        return $entity->scholarship->duration . ' ' . __('Years');
    }

    public function onGetBond(Event $event, Entity $entity)
    {
        return $entity->scholarship->bond . ' ' . __('Years');
    }

    public function onGetRequirements(Event $event, Entity $entity)
    {
        return $entity->scholarship->requirements;
    }

    public function onGetInstructions(Event $event, Entity $entity)
    {
        return $entity->scholarship->instructions;
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
            $value = $this->interestRateOptions[$interestRateType];
            return $value;
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

    public function onUpdateFieldDateOfBirth(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $dateOfBirthValue = '';
            if ($entity->has('applicant') && $entity->applicant->has('date_of_birth')) {
                $applicantEntity = $entity->applicant;
                $dateOfBirthValue = $this->formatDate($applicantEntity->date_of_birth);
            }

            $attr['value'] = $dateOfBirthValue;
            $attr['attr']['value'] = $dateOfBirthValue;
        }

        return $attr;
    }

    public function onUpdateFieldFinancialAssistanceTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $FinancialAssistanceTypesTable = TableRegistry::get('Scholarship.FinancialAssistanceTypes');
            $financialAssistanceTypeOptions = $FinancialAssistanceTypesTable->getList()->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $financialAssistanceTypeOptions;
            $attr['onChangeReload'] = 'changeFinancialAssistanceType';
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->scholarship->scholarship_financial_assistance_type_id;
            $attr['attr']['value'] = $entity->scholarship->financial_assistance_type->name;
        }

        return $attr;
    }

    public function onUpdateFieldScholarshipId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $entity = $attr['entity'];

            $scholarshipOptions = [];
            if ($entity->has('applicant_id') && $entity->has('financial_assistance_type_id')) {
                $applicantId = $entity->applicant_id;
                $financialAssistanceTypeId = $entity->financial_assistance_type_id;

                $scholarshipOptions = $this->Scholarships->getAvailableScholarships(['applicant_id' => $applicantId, 'financial_assistance_type_id' => $financialAssistanceTypeId]);
            }

            $attr['type'] = 'select';
            $attr['onChangeReload'] = 'changeScholarship';
            $attr['options'] = $scholarshipOptions;
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];
            
            $attr['type'] = 'readonly';
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

            $attr['value'] = $value;
            $attr['attr']['value'] = $value;
        }

        return $attr;
    }

    public function onUpdateFieldDuration(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $value = '';
            if (isset($entity->scholarship->duration) && strlen($entity->scholarship->duration) > 0) {
                $value = $entity->scholarship->duration . ' ' . __('Years');
            }

            $attr['value'] = $value;
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

            $attr['value'] = $value;
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

            $attr['value'] = $value;
            $attr['attr']['value'] = $value;
        }

        return $attr;
    }

    public function setupApplicantFields($entity = null)
    {
        $this->field('openemis_no', [
            'type' => 'disabled',
            'fieldName' => 'applicant.openemis_no'
        ]);
        $this->field('applicant_name', [
            'type' => 'disabled',
            'fieldName' => 'applicant.name',
            'attr' => ['label' => __('Applicant')]
        ]);
        $this->field('applicant_id', [
            'type' => 'hidden'
        ]);
        $this->field('date_of_birth', [
            'type' => 'disabled',
            'fieldName' => 'applicant.date_of_birth',
            'entity' => $entity
        ]);
        $this->field('gender', [
            'type' => 'disabled',
            'fieldName' => 'applicant.gender.name'
        ]);
        $this->field('identity_type', [
            'type' => 'disabled',
            'fieldName' => 'applicant.main_identity_type.name'
        ]);
        $this->field('identity_number', [
            'type' => 'disabled',
            'fieldName' => 'applicant.identity_number'
        ]);
    }

    public function setupScholarshipFields($entity = null)
    {
        $isLoan = false;
        $FinancialAssistanceTypesTable = TableRegistry::get('Scholarship.FinancialAssistanceTypes');
        if ($entity->has('financial_assistance_type_id')) {
            // for add
            $isLoan = $FinancialAssistanceTypesTable->is($entity->financial_assistance_type_id, 'LOAN');
        } elseif ($entity->has('scholarship') && $entity->scholarship->has('scholarship_financial_assistance_type_id')) {
            // for view and edit
            $isLoan = $FinancialAssistanceTypesTable->is($entity->scholarship->scholarship_financial_assistance_type_id, 'LOAN');
        }

        $this->field('financial_assistance_type_id', [
            'entity' => $entity
        ]);
        $this->field('scholarship_id', [
            'type' => 'string', // required in view because composite primary key is set to hidden by default
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
            'attr' => [
                'require' => false,
                'label' => $this->addCurrencySuffix('Annual Award Amount')
            ]
        ]);
        $this->field('duration', [
            'type' => 'disabled',
            'entity' => $entity
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

    public function onApproveScholarship(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $ScholarshipRecipients = TableRegistry::get('Scholarship.ScholarshipRecipients');
        $RecipientActivities = TableRegistry::get('Scholarship.RecipientActivities');
        $RecipientActivityStatuses = TableRegistry::get('Scholarship.RecipientActivityStatuses');
        
        $recipientActivityStatusEntity = $RecipientActivityStatuses->find()
            ->where([
                $RecipientActivityStatuses->aliasField('international_code') => 'APPLICATION_APPROVED'
            ])
            ->first();

        $entity = $this->find()->where([$this->aliasField('id') => $id])->first();
        $newRecipient = [
            'recipient_id' => $entity->applicant_id,
            'scholarship_id' => $entity->scholarship_id,
            'scholarship_recipient_activity_status_id' => $recipientActivityStatusEntity->id
        ];

        $newActivity = [
            'date' => date("Y-m-d"),
            'recipient_id' => $entity->applicant_id,
            'scholarship_id' => $entity->scholarship_id,
            'prev_recipient_activity_status_name' => 'New',
            'recipient_activity_status_name' => $recipientActivityStatusEntity->name
        ];

        $newEntity = $ScholarshipRecipients->newEntity($newRecipient);
        $newActivityEntity = $RecipientActivities->newEntity($newActivity);
        $ScholarshipRecipients->save($newEntity);
        $RecipientActivities->save($newActivityEntity);
    }

    public function onWithdrawScholarship(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $ScholarshipRecipients = TableRegistry::get('Scholarship.ScholarshipRecipients');
        $RecipientActivities = TableRegistry::get('Scholarship.RecipientActivities');

        $entity = $this->find()->where([$this->aliasField('id') => $id])->first();
        $existingRecipient = [
            'recipient_id' => $entity->applicant_id,
            'scholarship_id' => $entity->scholarship_id
        ];

        try {
            $existingEntity = $ScholarshipRecipients->get($existingRecipient);
            $ScholarshipRecipients->delete($existingEntity);
            
            $RecipientActivities->deleteAll([
                'recipient_id' => $entity->applicant_id,
                'scholarship_id' => $entity->scholarship_id 
            ]);

        } catch (RecordNotFoundException $e) {
            Log::write('debug', $e->getMessage());
        }
    }

    public function getModelAlertData($threshold)
    {
        $thresholdArray = json_decode($threshold, true);
        
        $conditionKey = $thresholdArray['condition'];
        $dayBefore = $thresholdArray['value'];
        $workflowCategory = $thresholdArray['category'];

        // 1 - Days before application close date
        $sqlConditions = [
            1 => ('DATEDIFF(Scholarships.application_close_date, NOW())' . ' BETWEEN 0 AND ' . $dayBefore), // before
        ];
        $record = [];
        
        if (array_key_exists($conditionKey, $sqlConditions)) { 
            $record = $this
                ->find()
                ->select([
                    $this->aliasField('applicant_id'),
                    $this->aliasField('assignee_id'),
                    $this->aliasField('scholarship_id'),
                    $this->aliasField('status_id'),
                    $this->aliasField('requested_amount'),
                    $this->aliasField('comments'),
                    $this->Applicants->aliasField('first_name'),
                    $this->Applicants->aliasField('middle_name'),
                    $this->Applicants->aliasField('third_name'),
                    $this->Applicants->aliasField('last_name'),
                    $this->Applicants->aliasField('preferred_name'),
                    $this->Applicants->aliasField('email'),
                    $this->Applicants->aliasField('address'),
                    $this->Applicants->aliasField('postal_code'),
                    $this->Applicants->aliasField('date_of_birth'),
                    $this->Scholarships->aliasField('code'),
                    $this->Scholarships->aliasField('name'),
                    $this->Scholarships->aliasField('description'),
                    $this->Scholarships->aliasField('application_close_date'),
                    $this->Scholarships->aliasField('application_open_date'),
                    $this->Scholarships->aliasField('maximum_award_amount'),
                    $this->Scholarships->aliasField('total_amount'),
                    $this->Scholarships->aliasField('duration'),
                    $this->Scholarships->aliasField('bond'),
                    $this->Assignees->aliasField('first_name'),
                    $this->Assignees->aliasField('middle_name'),
                    $this->Assignees->aliasField('third_name'),
                    $this->Assignees->aliasField('last_name'),
                    $this->Assignees->aliasField('preferred_name')
                ])
                ->contain([
                    $this->Scholarships->alias(),
                    $this->Applicants->alias(),
                    $this->Statuses->alias(),
                    $this->Assignees->alias()
                ])
                ->where([
                    $this->Statuses->aliasField('category') => $workflowCategory,
                    $sqlConditions[$conditionKey]
                ])
                ->hydrate(false)
                ->toArray();
        }
        return $record;
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
                $this->aliasField('applicant_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('status_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Applicants->aliasField('openemis_no'),
                $this->Applicants->aliasField('first_name'),
                $this->Applicants->aliasField('middle_name'),
                $this->Applicants->aliasField('third_name'),
                $this->Applicants->aliasField('last_name'),
                $this->Applicants->aliasField('preferred_name'),
                $this->Scholarships->aliasField('code'),
                $this->Scholarships->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Applicants->alias(), $this->Scholarships->alias(), $this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $queryString = $this->paramsEncode(['applicant_id' => $row->applicant_id, 'scholarship_id' => $row->scholarship_id]);
                    $url = [
                        'plugin' => 'Scholarship',
                        'controller' => 'Scholarships',
                        'action' => 'Applications',
                        'view',
                        $queryString,
                        'queryString' => $queryString
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }
                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s applying for %s'), $row->applicant->name_with_id, $row->scholarship->code_name);
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
