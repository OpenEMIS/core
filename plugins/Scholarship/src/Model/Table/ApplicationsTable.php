<?php
namespace Scholarship\Model\Table;

use ArrayObject;

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

    CONST SCHOLARSHIP_LOANS = 2;
    
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
                        return ($context['data']['financial_assistance_type_id'] == self::SCHOLARSHIP_LOANS );
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
        $this->field('requested_amount', ['visible' => false]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add']['url'])) {
            $extra['toolbarButtons']['add']['url']['controller'] = 'ApplicantsDirectory';
            $extra['toolbarButtons']['add']['url']['action'] = 'index';
            $extra['toolbarButtons']['add']['attr']['title'] = __('Apply');
            unset($extra['toolbarButtons']['add']['url'][0]);
        }

        // setup fields
        $this->field('comment', ['visible' => false]);
        $this->field('scholarship_id', ['type' => 'string']);
        $this->setupApplicantFields();

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
                'Statuses' => [
                    'fields' => [
                        'Statuses.name'
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
                ],
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
                ]
            ]);

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

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        // remove queryString when redirect
        $extra['redirect'] = $this->url('index', false);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['back']['url'])) {
            $extra['toolbarButtons']['back']['url']['controller'] = 'ApplicantsDirectory';
            $extra['toolbarButtons']['back']['url']['action'] = 'index';
            unset($extra['toolbarButtons']['back']['url'][0]);
            unset($extra['toolbarButtons']['back']['url']['queryString']);
        }

        $applicantId = $this->ControllerAction->getQueryString('applicant_id');

        if ($applicantId) {
            $applicantEntity = $this->Applicants->get($applicantId, ['contain' => ['Genders', 'MainIdentityTypes']]);

            $scholarshipEntity = null;
            if (!empty($this->request->data[$this->alias()]['scholarship_id'])) {
                $scholarshipId = $this->request->data[$this->alias()]['scholarship_id'];
                $scholarshipEntity = $this->Scholarships->get($scholarshipId, ['contain' => ['AcademicPeriods']]);
            }

            // setup fields
            $this->setupApplicantFields($applicantEntity);
            $this->field('scholarship_details_header', ['type' => 'section', 'title' => __('Apply for Scholarship')]);
            $this->setupScholarshipFields($scholarshipEntity);
            $this->field('assignee_id');
        } else {
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {   
        $applicantId = $this->ControllerAction->getQueryString('applicant_id');
        $scholarshipId = $this->ControllerAction->getQueryString('scholarship_id');
        $applicantEntity = $this->Applicants->get($applicantId, ['contain' => ['Genders', 'MainIdentityTypes']]);
        $scholarshipEntity = $this->Scholarships->get($scholarshipId, ['contain' => ['AcademicPeriods' , 'FinancialAssistanceTypes']]);

        $this->setupApplicantFields($applicantEntity);
        $this->field('scholarship_details_header', ['type' => 'section', 'title' => __('Apply for Scholarship')]);
        $this->setupScholarshipFields($scholarshipEntity);
        $this->field('assignee_id');
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $applicantId = $this->ControllerAction->getQueryString('applicant_id');
        $applicantName = $this->Applicants->get($applicantId)->name;
        $this->controller->set('contentHeader', $applicantName. ' - ' .__('Overview'));

        $tabElements = $this->controller->getScholarshipTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());

        // setup fields
        $this->field('code');
        $this->field('instructions', ['type' => 'text']);
        $this->setupScholarshipFields();
        $this->setFieldOrder(['academic_period_id', 'code', 'scholarship_id', 'financial_assistance_type_id', 'description', 'maximum_award_amount', 'bond', 'requirements', 'instructions']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Scholarships' => ['AcademicPeriods', 'FinancialAssistanceTypes', 'Loans.PaymentFrequencies']]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->has('scholarship') && $entity->scholarship->has('financial_assistance_type')) {
            switch ($entity->scholarship->financial_assistance_type->code) {
                case 'SCHOLARSHIP':
                    // No implementation
                    break;
                case 'LOAN':
                    $this->field('requested_amount', [
                        'attr' => ['label' => $this->addCurrencySuffix('Requested Amount')],
                        'visible' => true
                    ]);
                    $this->field('interest_rate', [
                        'attr' => ['label' => __('Interest Rate').' (%)'],
                        'after' => 'bond'
                    ]);
                    $this->field('interest_rate_type', [
                        'after' => 'interest_rate'
                    ]);
                    $this->field('scholarship_payment_frequency_id', [
                        'attr' => ['label' => __('Payment Frequency')],
                        'after' => 'interest_rate_type'
                    ]);
                    $this->field('loan_term', [
                        'after' => 'scholarship_payment_frequency_id'
                    ]);
                    break;
            }
        }
    }

    // index fields
    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->applicant->openemis_no;
    }

    public function onGetDateOfBirth(Event $event, Entity $entity)
    {
        return $this->formatDate($entity->applicant->date_of_birth);
    }

    public function onGetGenderId(Event $event, Entity $entity)
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

    public function onGetCode(Event $event, Entity $entity)
    {
        return $entity->scholarship->code;
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

    public function onUpdateFieldFinancialAssistanceTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $FinancialAssistanceTypes = TableRegistry::get('Scholarship.FinancialAssistanceTypes');
            $financialAssistanceTypeOptions = $FinancialAssistanceTypes->getList()->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $financialAssistanceTypeOptions;
            $attr['onChangeReload'] = 'changeFinancialAssistanceTypeId';

        } elseif ($action == 'edit') {
            $entity = $attr['entity'];
            
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->scholarship_financial_assistance_type_id;
            $attr['attr']['value'] = $entity->financial_assistance_type->name;   
        }
        return $attr;
    }

    public function addOnChangeFinancialAssistanceTypeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('scholarship_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['scholarship_id']);
            }
        }
    }

    public function onUpdateFieldScholarshipId(Event $event, array $attr, $action, $request)
    {   
        if ($action == 'add' || $action == 'edit') {
            
            $FinancialAssistanceTypes = TableRegistry::get('Scholarship.FinancialAssistanceTypes');
            $financialAssistanceTypeOptions = $FinancialAssistanceTypes
                 ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code'
                ])
                ->order([$FinancialAssistanceTypes->aliasField('id')])
                ->toArray();
            
            if ($action == 'add') {
                $scholarshipOptions = [];

                if (!empty($request->data[$this->alias()]['financial_assistance_type_id']) && !empty($request->data[$this->alias()]['applicant_id'])) {
                    $applicantId = $request->data[$this->alias()]['applicant_id'];
                    $financialAssistanceTypeId = $request->data[$this->alias()]['financial_assistance_type_id'];

                    $scholarshipOptions = $this->Scholarships->getAvailableScholarships(['applicant_id' => $applicantId, 'financial_assistance_type_id' => $financialAssistanceTypeId]);

                    if (!empty($request->data[$this->alias()]['scholarship_id'])) {
                        $scholarshipId = $this->request->data[$this->alias()]['scholarship_id'];
                    }
                }

                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;
                $attr['options'] = $scholarshipOptions;

            } else {
                $entity = $attr['entity'];
                $scholarshipId = $entity->id;
                $financialAssistanceTypeId = $entity->scholarship_financial_assistance_type_id;
                
                $attr['type'] = 'readonly';
                $attr['value'] = $scholarshipId;
                $attr['attr']['value'] = $entity->name; 
            }

            if(isset($financialAssistanceTypeId) && isset($scholarshipId)) {
                switch ($financialAssistanceTypeOptions[$financialAssistanceTypeId]) {
                    case 'SCHOLARSHIP':
                        // No implementation
                        break;
                    case 'LOAN':

                        $scholarshipEntity = $this->Scholarships->get($scholarshipId, ['contain' => ['Loans.PaymentFrequencies']]);

                        $this->field('requested_amount', [
                            'visible' => true,
                            'type' => 'integer',
                            'attr' => ['label' => $this->addCurrencySuffix('Requested Amount')]
                        ]);
                        $this->field('interest_rate', [
                            'type' => 'disabled',
                            'attr' => [
                                'label' => __('Interest Rate') . ' (%)',
                                'value' => $scholarshipEntity->loan->interest_rate
                            ]
                        ]);
                        $this->field('interest_rate_type', [
                            'type' => 'disabled',
                            'attr' => [
                                'label' => __('Interest Rate Type'),
                                'value' => $this->interestRateOptions[$scholarshipEntity->loan->interest_rate_type]
                            ]
                        ]);
                        $this->field('payment_frequency_id', [
                            'type' => 'disabled',
                            'attr' => [
                                'label' => __('Payment Frequency'),
                                'value' => $scholarshipEntity->loan->payment_frequency->name
                            ]
                        ]);
                        $this->field('loan_term', [
                            'type' => 'disabled',
                            'attr' => [
                                'label' => __('Loan Term'),
                                'value' => $scholarshipEntity->loan->loan_term . ' ' . __('Years')
                            ]
                        ]);
                        break;
                }
            }
        }
        return $attr;
    }

    public function setupApplicantFields($entity = null)
    {
        $this->field('openemis_no', ['type' => 'disabled']);
        $this->field('applicant_id', ['type' => 'readonly']);
        $this->field('date_of_birth', ['type' => 'disabled']);
        $this->field('gender_id', ['type' => 'disabled']);
        $this->field('identity_type', ['type' => 'disabled']);
        $this->field('identity_number', ['type' => 'disabled']);

        if (!is_null($entity)) {
            $this->fields['openemis_no']['attr']['value'] = $entity->openemis_no;
            $this->fields['applicant_id']['attr']['value'] = $entity->name;
            $this->fields['applicant_id']['value'] = $entity->id;
            $this->fields['date_of_birth']['attr']['value'] = $this->formatDate($entity->date_of_birth);
            $this->fields['gender_id']['attr']['value'] = $entity->has('gender') ? $entity->gender->name : '';
            $this->fields['identity_type']['attr']['value'] = $entity->has('main_identity_type') ? $entity->main_identity_type->name : '';
            $this->fields['identity_number']['attr']['value'] = $entity->identity_number;
        }
    }

    public function setupScholarshipFields($entity = null)
    {
        $this->field('financial_assistance_type_id', ['entity' => $entity]);
        $this->field('scholarship_id', ['type' => 'string','entity' => $entity]);
        $this->field('academic_period_id', ['type' => 'disabled']);
        $this->field('description', ['type' => 'text', 'attr' => ['disabled' => 'disabled']]);
        $this->field('maximum_award_amount', [
            'type' => 'disabled',
            'attr' => ['label' => $this->addCurrencySuffix('Maximum Award Amount')]
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
        $ScholarshipRecipient = TableRegistry::get('Institution.ScholarshipRecipient');

        $entity = $this->get($id);
        $newRecipient = [
            'recipient_id' => $entity->applicant_id,
            'scholarship_id' => $entity->scholarship_id
        ];

        $newEntity = $ScholarshipRecipient->newEntity($newRecipient);
        $ScholarshipRecipient->save($newEntity);
    }

    public function onWithdrawScholarship(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $ScholarshipRecipient = TableRegistry::get('Institution.ScholarshipRecipient');

        $entity = $this->get($id);
        $existingRecipient = [
            'recipient_id' => $entity->applicant_id,
            'scholarship_id' => $entity->scholarship_id
        ];

        try {
            $existingEntity = $this->get($existingRecipient);
            $ScholarshipRecipient->delete($existingEntity);

        } catch (RecordNotFoundException $e) {
            Log::write('debug', $e->getMessage());
        }
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
