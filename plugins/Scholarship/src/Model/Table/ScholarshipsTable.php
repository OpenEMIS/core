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
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class ScholarshipsTable extends ControllerActionTable
{
    use OptionsTrait;

    CONST SELECT_FIELD_OF_STUDIES = 1;
    CONST SELECT_ALL_FIELD_OF_STUDIES = -1;

    private $fieldOfStudySelection = [];
    private $interestRateOptions = [];
    private $currency = [];

    public function initialize(array $config)
    {
        $this->table('scholarships');
        parent::initialize($config);

        $this->belongsTo('FinancialAssistanceTypes', ['className' => 'Scholarship.FinancialAssistanceTypes', 'foreignKey' => 'scholarship_financial_assistance_type_id']);
        $this->belongsTo('FundingSources', ['className' => 'Scholarship.FundingSources', 'foreignKey' => 'scholarship_funding_source_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->hasOne('Loans', ['className' => 'Scholarship.Loans', 'foreignKey' => 'scholarship_id' , 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->hasMany('AttachmentTypes', ['className' => 'Scholarship.AttachmentTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Applications', ['className' => 'Scholarship.Applications', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RecipientAcademicStandings', ['className' => 'Scholarship.RecipientAcademicStandings', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RecipientActivities', ['className' => 'Scholarship.RecipientActivities', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RecipientCollections', ['className' => 'Scholarship.RecipientCollections', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RecipientDisbursements', ['className' => 'Scholarship.RecipientDisbursements', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RecipientPaymentStructureEstimates', ['className' => 'Scholarship.RecipientPaymentStructureEstimates', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RecipientPaymentStructures', ['className' => 'Scholarship.RecipientPaymentStructures', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->belongsToMany('FieldOfStudies', [
            'className' => 'Education.EducationFieldOfStudies',
            'joinTable' => 'scholarships_field_of_studies',
            'foreignKey' => 'scholarship_id',
            'targetForeignKey' => 'education_field_of_study_id',
            'through' => 'Scholarship.ScholarshipsFieldOfStudies',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->setDeleteStrategy('restrict');

        $this->fieldOfStudySelection = $this->getSelectOptions($this->aliasField('field_of_study_selection'));
        $this->interestRateOptions = $this->getSelectOptions($this->aliasField('interest_rate'));
        $this->currency = TableRegistry::get('Configuration.ConfigItems')->value('currency');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        if (in_array($this->action, ['view', 'edit'])) {
            $scholarshipId = $this->ControllerAction->getQueryString('id');
            $scholarshipName = $this->get($scholarshipId)->name;

            $Navigation->addCrumb($scholarshipName);
            $Navigation->addCrumb(__('Overview'));
        } else {
            $Navigation->addCrumb(__($this->getHeader($this->alias())));
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
            ])
            ->requirePresence('field_of_studies')
            ->add('field_of_studies', 'notEmpty', [
                'rule' => function ($value, $context) {
                    return isset($value['_ids']) ? !empty($value['_ids']) : true;
                },
                'message' => __('This field cannot be left empty')
            ])
            ->add('date_application_close', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_application_open', true]
            ])
            ->add('total_amount', [
                'ruleCompareMaximumAwardAmount' => [
                    'rule' => ['compareValues', 'maximum_award_amount'],
                    'message' => __('Total Amount must be greater than Maximum Award Amount')
                ],
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                    'message' => __('Value cannot be more than two decimal places')
                ]
            ])
            ->add('maximum_award_amount', 'validateDecimal', [
                'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                'message' => __('Value cannot be more than two decimal places')
            ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('description', ['visible' => false]);
        $this->field('scholarship_financial_assistance_type_id', ['visible' => false]);
        $this->field('scholarship_funding_source_id', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('total_amount', ['visible' => false]);
        $this->field('requirements', ['visible' => false]);
        $this->field('instructions', ['visible' => false]);
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $entity->field_of_study_selection = self::SELECT_FIELD_OF_STUDIES;
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $isSelectAll = $this->checkIsSelectAll($entity);

        if ($isSelectAll) {
            $entity->field_of_study_selection = self::SELECT_ALL_FIELD_OF_STUDIES;
        } else {
            $entity->field_of_study_selection = self::SELECT_FIELD_OF_STUDIES;
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $this->setupTabElements();
        $this->setOverviewHeader($entity);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $code = $entity->financial_assistance_type->code;
        switch ($code) {
            case 'SCHOLARSHIP':
                // No implementation
                break;
            case 'LOAN':
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

        $this->setupFields($entity);
        $this->setupTabElements();
        $this->setOverviewHeader($entity);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['FinancialAssistanceTypes','FieldOfStudies', 'Loans.PaymentFrequencies']);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->AttachmentTypes->alias(), $this->FieldOfStudies->alias()
        ];
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->has('field_of_study_selection') && $entity->field_of_study_selection == self::SELECT_ALL_FIELD_OF_STUDIES) {
            $ScholarshipsFieldOfStudies = TableRegistry::get('Scholarship.ScholarshipsFieldOfStudies');

            $data = [
                'scholarship_id' => $entity->id,
                'education_field_of_study_id' => self::SELECT_ALL_FIELD_OF_STUDIES
            ];
            $ScholarshipsFieldOfStudiesEntity = $ScholarshipsFieldOfStudies->newEntity($data);

            if (!$ScholarshipsFieldOfStudies->save($ScholarshipsFieldOfStudiesEntity)) {
                Log::write('debug', $ScholarshipsFieldOfStudiesEntity->errors());
            }
        }
    }

    public function onGetFieldOfStudies(Event $event, Entity $entity)
    {
        $isSelectAll = $this->checkIsSelectAll($entity);

        if ($this->action == 'view' && $isSelectAll) {
            $list = $this->FieldOfStudies
                ->find('list')
                ->find('order')
                ->find('visible')
                ->toArray();

            return (!empty($list))? implode(', ', $list) : ' ';
        }
    }

    public function onGetBond(Event $event, Entity $entity)
    {
        return $entity->bond . ' ' . __('Years');
    }

    public function onGetInterestRate(Event $event, Entity $entity)
    {
        return $entity->loan->interest_rate;
    }

    public function onGetInterestRateType(Event $event, Entity $entity)
    {
        $interestRateType = $entity->loan->interest_rate_type;
        $value = $this->interestRateOptions[$interestRateType];
        return $value;
    }

    public function onGetScholarshipPaymentFrequencyId(Event $event, Entity $entity)
    {
        return $entity->loan->payment_frequency->name;
    }

    public function onGetLoanTerm(Event $event, Entity $entity)
    {
        return $entity->loan->loan_term . ' ' . __('Years');
    }

    public function onUpdateFieldFieldOfStudySelection(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = $this->fieldOfStudySelection;
            $attr['select'] = false;
            $attr['onChangeReload'] = 'changeFieldOfStudy';
        }
        return $attr;
    }

    public function onUpdateFieldFieldOfStudies(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $fieldOfStudySelection = $entity->field_of_study_selection;

            if ($fieldOfStudySelection == self::SELECT_ALL_FIELD_OF_STUDIES) {
                $attr['value'] = self::SELECT_ALL_FIELD_OF_STUDIES;
                $attr['attr']['value'] = __('All Field Of Studies Selected');
                $attr['type'] = 'readonly';
            } else {
                $fieldOfStudyOptions = $this->FieldOfStudies->getList()->toArray();
                $attr['options'] = $fieldOfStudyOptions;
            }
        }
        return $attr;
    }

    public function onUpdateFieldScholarshipFinancialAssistanceTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $financialAssistanceTypeId = $entity->scholarship_financial_assistance_type_id;

            if (!$entity->isNew()) { // edit not allow to change field type
                $attr['type'] = 'readonly';
                $attr['value'] = $financialAssistanceTypeId;
                $attr['attr']['value'] = $entity->financial_assistance_type->name;
            }

            if (!empty($financialAssistanceTypeId)) {
                $financialAssistanceTypeOptions = $this->FinancialAssistanceTypes
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code'
                    ])
                    ->order([$this->FinancialAssistanceTypes->aliasField('id')])
                    ->toArray();

                switch ($financialAssistanceTypeOptions[$financialAssistanceTypeId]) {
                    case 'SCHOLARSHIP':
                        // No implementation
                        break;
                    case 'LOAN':
                        $PaymentFrequencies = TableRegistry::get('Scholarship.PaymentFrequencies');

                        // fields for loan type
                        $this->field('loan.interest_rate', [
                            'type' => 'integer',
                            'attr' => ['label' => __('Interest Rate'). ' (%)'],
                            'after' => 'bond'
                        ]);
                        $this->field('loan.interest_rate_type', [
                            'type' => 'select',
                            'attr' => [
                                'label' => __('Interest Rate Type'),
                                'select' => true,
                                'options' => $this->interestRateOptions
                            ],
                            'after' => 'loan.interest_rate'
                        ]);
                        $this->field('loan.scholarship_payment_frequency_id', [
                            'type' => 'select',
                            'attr' => [
                                'label' => __('Payment Frequency'),
                                'select' => true,
                                'options' => $PaymentFrequencies->getList()->toArray()
                            ],
                            'after' => 'loan.interest_rate_type'
                        ]);
                        $this->field('loan.loan_term', [
                            'type' => 'select',
                            'attr' => [
                                'label' => __('Loan Term'),
                                'select' => true,
                                'options' => $this->getLoanTermOptions(20)
                            ],
                            'after' => 'loan.scholarship_payment_frequency_id'
                        ]);
                        break;
                }
            }
            $attr['onChangeReload'] = 'changeFinancialAssistanceType';
        }
        return $attr;
    }

    public function onUpdateFieldBond(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = $this->getBondOptions(20);
        }
        return $attr;
    }

    public function setupFields($entity = null)
    {
        $this->field('scholarship_financial_assistance_type_id', [
            'type' => 'select',
            'attr' => ['label' => __('Financial Assistance Type')],
            'after' => 'description',
            'entity' => $entity
        ]);
        $this->field('scholarship_funding_source_id', [
            'type' => 'select',
            'attr' => ['label' => __('Funding Source')],
            'after' => 'scholarship_financial_assistance_type_id'
        ]);
        $this->field('academic_period_id', [
            'type' => 'select',
            'options' => $this->AcademicPeriods->getYearList(['isEditable' => true]),
            'after' => 'scholarship_funding_source_id'
        ]);
        $this->field('field_of_study_selection', [
            'type' => 'select',
            'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true],
            'after' => 'academic_period_id'
        ]);
        $this->field('field_of_studies', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select Field Of Studies'),
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true],
            'attr' => ['required' => true],
            'entity' => $entity,
            'after' => 'field_of_study_selection'
        ]);
        $this->field('maximum_award_amount', [
            'type' => 'integer',
            'attr' => ['label' => $this->addCurrencySuffix('Maximum Award Amount')],
            'after' => 'date_application_close'
        ]);
        $this->field('total_amount', [
            'type' => 'integer',
            'attr' => ['label' => $this->addCurrencySuffix('Total Amount')],
            'after' => 'maximum_award_amount'
        ]);
        $this->field('bond', [
            'type' => 'select',
            'after' => 'total_amount'
        ]);
    }

    public function setupTabElements()
    {
        $tabElements = $this->ScholarshipTabs->getScholarshipTabs();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $params = ['id' => $entity->id];

        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = $this->ControllerAction->setQueryString($buttons['view']['url'], $params);
        }

        if (isset($buttons['edit']['url'])) {
            $buttons['edit']['url'] = $this->ControllerAction->setQueryString($buttons['edit']['url'], $params);
        }

        return $buttons;
    }

    public function setOverviewHeader($entity)
    {
        $this->controller->set('contentHeader', $entity->name. ' - ' .__('Overview'));
    }

    public function getBondOptions($maxYears)
    {
        $bondOptions = [];

        for ($i=0; $i<=$maxYears; $i++) {
            $bondOptions[$i] = $i . ' ' . __('Years');
        }

        return $bondOptions;
    }

    public function getLoanTermOptions($maxYears)
    {
        $loanTermOptions = [];

        for ($i=1; $i<=$maxYears; $i++) {
            $loanTermOptions[$i] = $i . ' ' . __('Years');
        }

        return $loanTermOptions;
    }

    public function checkIsSelectAll($entity)
    {
        $ScholarshipsFieldOfStudies = TableRegistry::get('Scholarship.ScholarshipsFieldOfStudies');

        $isSelectAll = $ScholarshipsFieldOfStudies->exists([
            $ScholarshipsFieldOfStudies->aliasField('scholarship_id') => $entity->id,
            $ScholarshipsFieldOfStudies->aliasField('education_field_of_study_id') => self::SELECT_ALL_FIELD_OF_STUDIES
        ]);
        return $isSelectAll;
    }

    public function addCurrencySuffix($label)
    {
        return __($label) . ' (' . $this->currency . ')';
    }

    public function getAvailableScholarships($options = [])
    {
        $todayDate = date("Y-m-d");
        $availableScholarships = [];
        $applicantId = array_key_exists('applicant_id', $options) ? $options['applicant_id'] : null;
        $financialAssistanceTypeId = array_key_exists('financial_assistance_type_id', $options) ? $options['financial_assistance_type_id'] : null;

        if (!is_null($applicantId) && !is_null($financialAssistanceTypeId)) {
            $availableScholarships = $this
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->notMatching('Applications', function ($q) use ($applicantId) {
                    return $q->where(['Applications.applicant_id' => $applicantId]);
                 })
                ->where([
                    $this->aliasField('scholarship_financial_assistance_type_id') => $financialAssistanceTypeId,
                    $this->aliasField('date_application_close >=') => $todayDate
                ])
                ->toArray();
        }
        return $availableScholarships;
    }
}
