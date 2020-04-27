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

    const SELECT_FIELD_OF_STUDIES = 1;
    const SELECT_ALL_FIELD_OF_STUDIES = -1;
    const MAX_YEARS = 20;

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

        $this->belongsToMany('AttachmentTypes', [
            'className' => 'Scholarship.AttachmentTypes',
            'joinTable' => 'scholarships_scholarship_attachment_types',
            'foreignKey' => 'scholarship_id',
            'targetForeignKey' => 'scholarship_attachment_type_id',
            'through' => 'Scholarship.ScholarshipsScholarshipAttachmentTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->setDeleteStrategy('restrict');

        $this->fieldOfStudySelection = $this->getSelectOptions($this->aliasField('field_of_study_selection'));
        $this->interestRateOptions = $this->getSelectOptions($this->aliasField('interest_rate'));
        $this->mandatoryOptions = $this->getSelectOptions('general.yesno');
        $this->currency = TableRegistry::get('Configuration.ConfigItems')->value('currency');

        $this->addBehavior('Excel', [
            'pages' => ['index']
        ]);
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
            $Navigation->addCrumb(__('Details'));
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
            ->requirePresence('bond')
            ->requirePresence('duration')
            ->add('field_of_studies', 'notEmpty', [
                'rule' => function ($value, $context) {
                    return isset($value['_ids']) ? !empty($value['_ids']) : true;
                },
                'message' => __('This field cannot be left empty')
            ])
            ->add('application_close_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'application_open_date', true]
            ])
            ->add('total_amount', [
                'ruleCompareMaximumAwardAmount' => [
                    'rule' => ['compareValues', 'maximum_award_amount'],
                    'message' => __('Total Award Amount must be greater than Annual Award Amount')
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $query
            ->contain([
                'Loans.PaymentFrequencies',
                'FieldOfStudies' => [
                    'fields' => [
                        'FieldOfStudies.name',
                        'ScholarshipsFieldOfStudies.scholarship_id'
                    ]
                ],
                'AttachmentTypes' => [
                    'fields' => [
                        'AttachmentTypes.name',
                        'ScholarshipsScholarshipAttachmentTypes.scholarship_id'
                    ]
                ],
                'FinancialAssistanceTypes',
                'FundingSources',
                'AcademicPeriods',
            ])
            ->select([
                'code' => $this->aliasField('code'),
                'name' => $this->aliasField('name'),
                'description' => $this->aliasField('description'),
                'application_open_date' => $this->aliasField('application_open_date'),
                'application_close_date' => $this->aliasField('application_close_date'),
                'maximum_award_amount' => $this->aliasField('maximum_award_amount'),
                'total_amount' => $this->aliasField('total_amount'),
                'duration' => $this->aliasField('duration'),
                'bond' => $this->aliasField('bond'),
                'requirements' => $this->aliasField('requirements'),
                'instructions' => $this->aliasField('instructions'),
                'scholarship_financial_assistance_type_id' => $this->aliasField('scholarship_financial_assistance_type_id'),
                'scholarship_funding_source_id' => $this->aliasField('scholarship_funding_source_id'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'financial_assistance_types' => 'FinancialAssistanceTypes.name', 
                'funding_sources' => 'FundingSources.name', 
                'academic_periods' => 'AcademicPeriods.name',
                'interest_rate' => 'Loans.interest_rate', 
                'interest_rate_type' => 'Loans.interest_rate_type', 
                'loan_term' => 'Loans.loan_term', 
                'payment_frequency_name' => 'PaymentFrequencies.name'
            ]);
    }

     public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
     {
            $newArray = [];
            $newArray[] = [
                'key' => 'Scholarships.code',
                'field' => 'code',
                'type' => 'string',
                'label' =>  __('Code')
            ];               
            $newArray[] = [
                'key' => 'Scholarships.name',
                'field' => 'name',
                'type' => 'string',
                'label' =>  __('Scholarship Name')
            ];
            $newArray[] = [
                'key' => 'Scholarships.description',
                'field' => 'description',
                'type' => 'string',
                'label' =>  __('Description')
            ];
            $newArray[] = [
                'key' => 'Scholarships.application_open_date',
                'field' => 'application_open_date',
                'type' => 'date',
                'label' =>  __('Application Open Date')
            ];
            $newArray[] = [
                'key' => 'Scholarships.application_close_date',
                'field' => 'application_close_date',
                'type' => 'date',
                'label' =>  __('Application Close Date')
            ];
            $newArray[] = [
                'key' => 'Scholarships.maximum_award_amount',
                'field' => 'maximum_award_amount',
                'type' => 'integer',
                'label' =>  __('Maximum Award Amount')
            ];
            $newArray[] = [
                'key' => 'Scholarships.total_amount',
                'field' => 'total_amount',
                'type' => 'string',
                'label' =>  __('Total Amount')
            ];
            $newArray[] = [
                'key' => 'Scholarships.duration',
                'field' => 'duration',
                'type' => 'integer',
                'label' =>  __('Duration')
            ];
            $newArray[] = [
                'key' => 'Scholarships.bond',
                'field' => 'bond',
                'type' => 'integer',
                'label' =>  __('Bond')
            ];
            $newArray[] = [
                'key' => 'Scholarships.requirements',
                'field' => 'requirements',
                'type' => 'string',
                'label' =>  __('Requirements')
            ];
            $newArray[] = [
                'key' => 'Scholarships.instructions',
                'field' => 'instructions',
                'type' => 'string',
                'label' =>  __('Instructions')
            ];
            $newArray[] = [
                'key' => 'FinancialAssistanceTypes.name',
                'field' => 'financial_assistance_types',
                'type' => 'string',
                'label' =>  __('Financial Assistance Types')
            ];
            $newArray[] = [
                'key' => 'FundingSources.name',
                'field' => 'funding_sources',
                'type' => 'string',
                'label' =>  __('Funding Sources')
            ];
            $newArray[] = [
                'key' => 'AcademicPeriods.name',
                'field' => 'academic_periods',
                'type' => 'string',
                'label' =>  __('Academic Periods')
            ];
            $newArray[] = [
                'key' => 'FieldOfStudies.name',
                'field' => 'all_field_of_studies',
                'type' => 'string',
                'label' =>  __('Field Of Studies')
            ];
            $newArray[] = [
                'key' => 'AttachmentTypes.name',
                'field' => 'all_attachment_types',
                'type' => 'string',
                'label' =>  __('Attachment Types')
            ];
            $newArray[] = [
                'key' => 'Loans.interest_rate',
                'field' => 'interest_rate',
                'type' => 'string',
                'label' => __('Interest Rate %')
            ];
            $newArray[] = [
                'key' => 'Loans.interest_rate_type',
                'field' => 'interest_rate_type',
                'type' => 'string',
                'label' => ''
            ];
            $newArray[] = [
                'key' => 'Loans.loan_term',
                'field' => 'loan_term',
                'type' => 'integer'
            ];
            $newArray[] = [
                'key' => 'PaymentFrequencies.name',
                'field' => 'payment_frequency_name',
                'type' => 'string',
                'label' => __('Payment Frequency')
            ];

            $fields->exchangeArray($newArray);
    }

    public function onExcelGetInterestRateType(Event $event, Entity $entity)
    {   
        $value = '';
        if ($entity->has('interest_rate_type')) {
            if (isset($entity->interest_rate_type)) {
                $interestRateType = $entity->interest_rate_type;
                $value = $this->interestRateOptions[$interestRateType];
            }
        }
        return $value;
    }

    public function onExcelGetAllAttachmentTypes(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('attachment_types')) {
            if (!empty($entity->attachment_types)) {
                foreach ($entity->attachment_types as $attachmentType) {
                        $return[] = $attachmentType->name;
                }
            }
        }
        return implode(', ', array_values($return));
    }

    public function onExcelGetAllFieldOfStudies(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('field_of_studies')) {
            if (!empty($entity->field_of_studies)) {
                foreach ($entity->field_of_studies as $studyField) {
                        $return[] = $studyField->name;
                }
            }else {
                $EducationFieldOfStudies = TableRegistry::get('Education.EducationFieldOfStudies')->getList()->toArray();
                foreach ($EducationFieldOfStudies as $educationFieldOfStudy) {
                    $return [] = $educationFieldOfStudy;
                }
            }
        }
        return implode(', ', array_values($return));
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->set('contentHeader', __($this->getHeader($this->alias())) . ' - ' . __('Details'));
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
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'FinancialAssistanceTypes',
                'FieldOfStudies',
                'AttachmentTypes',
                'Loans.PaymentFrequencies'
            ]);
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'maximum_award_amount') {
            return __('Annual Award Amount');
        } elseif ($field == 'name') {
            return __('Scholarship Name');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetBond(Event $event, Entity $entity)
    {
        return $entity->bond . ' ' . __('Years');
    }

    public function onGetDuration(Event $event, Entity $entity)
    {
        return $entity->duration . ' ' . __('Years');
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
                                'label' => __('Loan Term (Year)'),
                                'select' => true,
                                'options' => $this->getLoanTermOptions(self::MAX_YEARS)
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
            $attr['options'] = $this->getBondOptions(self::MAX_YEARS);
        }
        return $attr;
    }

    public function onUpdateFieldDuration(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = $this->getDurationOptions(self::MAX_YEARS);
        }
        return $attr;
    }

    public function addEditOnSelectAttachmentType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $fieldKey = 'attachment_types';

        if (!isset($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if (isset($data[$this->alias()]['attachment_type_id'])) {
            $selectedAttachmentType = $data[$this->alias()]['attachment_type_id'];
            $attachmentTypeEntity = $this->AttachmentTypes->get($selectedAttachmentType);

            $data[$this->alias()][$fieldKey][] = [
                'id' => $attachmentTypeEntity->id,
                'name' => $attachmentTypeEntity->name,
                'visible' => $attachmentTypeEntity->visible,
                '_joinData' => [
                    'is_mandatory' => 0
                ]
            ];
        }
    }

    public function onGetCustomAttachmentTypeElement(Event $event, $action, $entity, $attr, $options = [])
    {
        if ($action == 'index') {
            // No implementation yet
        } elseif ($action == 'view') {
            $tableHeaders = [__('Name') , __('Mandatory')];
            $tableCells = [];

            if ($entity->has('attachment_types')) {
                foreach ($entity->attachment_types as $key => $obj) {
                    $rowData = [];
                    $rowData[] = $obj->name;
                    $isMandatory = $obj->_joinData->is_mandatory;
                    if ($isMandatory) {
                        $rowData[] = "<i class='fa fa-check'></i>";
                    } else {
                        $rowData[] = "<i class='fa fa-close'></i>";
                    }

                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        } elseif ($action == 'add' || $action == 'edit') {
            $form = $event->subject()->Form;
            $form->unlockField($attr['model'] . '.attachment_types');

            $cellCount = 0;
            $tableHeaders = [__('Name') , __('Mandatory'), ''];
            $tableCells = [];

            $arrayAttachmentTypes = [];
            if ($this->request->is(['get'])) {
                // edit
                if (!$entity->isNew() && $entity->has('attachment_types')) {
                    foreach ($entity->attachment_types as $key => $obj) {
                        $arrayAttachmentTypes[] = [
                            'id' => $obj->id,
                            'name' => $obj->name,
                            'visible' => $obj->visible,
                            '_joinData' => [
                                'is_mandatory' => $obj->_joinData->is_mandatory
                            ]
                        ];
                    }
                }
            } elseif ($this->request->is(['post', 'put'])) {
                $requestData = $this->request->data;

                if (isset($requestData[$this->alias()]['attachment_types'])) {
                    foreach ($requestData[$this->alias()]['attachment_types'] as $key => $obj) {
                        $arrayAttachmentTypes[] = $obj;
                    }
                }
            }

            // options
            $attachmentTypeOptions = $this->AttachmentTypes->getList()->toArray();

            if (!empty($arrayAttachmentTypes)) {
                $usedAttachmentTypes = $this->getUsedAttachmentTypes($entity->id);

                foreach ($arrayAttachmentTypes as $key => $obj) {
                    $fieldPrefix = $attr['model'] . '.attachment_types.' . $cellCount++;
                    $joinDataPrefix = $fieldPrefix . '._joinData';

                    $cellData = $obj['name'];
                    $cellData .= $form->hidden($fieldPrefix.".id", ['value' => $obj['id']]);
                    $cellData .= $form->hidden($fieldPrefix.".name", ['value' => $obj['name']]);
                    $cellData .= $form->hidden($fieldPrefix.".visible", ['value' => $obj['visible']]);

                    $mandatoryInputOptions = [
                        'type' => 'select',
                        'label' => false,
                        'options' => $this->mandatoryOptions,
                        'default' => $obj['_joinData']['is_mandatory'],
                        'value' => $obj['_joinData']['is_mandatory']
                    ];
                    $mandatoryCellData = $form->input("$joinDataPrefix.is_mandatory", $mandatoryInputOptions);

                    $rowData = [];
                    $rowData[] = $cellData;
                    $rowData[] = $mandatoryCellData;

                    if (in_array($obj['id'], $usedAttachmentTypes)) {
                        $rowData[] = __('In use');
                    } else {
                        $rowData[] = '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
                    }

                    $tableCells[] = $rowData;

                    // remove selected option from dropdown list
                    if (isset($attachmentTypeOptions[$obj['id']])) {
                        unset($attachmentTypeOptions[$obj['id']]);
                    }
                }
            }

            $attr['options'] = $attachmentTypeOptions;
            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $event->subject()->renderElement('../ControllerAction/table_with_dropdown', ['attr' => $attr]);
    }

    public function setupFields($entity = null)
    {
        $this->field('name', [
            'attr' => ['label' => __('Scholarship Name')],
            'after' => 'code',
        ]);
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
            'attr' => ['label' => $this->addCurrencySuffix('Annual Award Amount')],
            'after' => 'application_close_date'
        ]);
        $this->field('total_amount', [
            'type' => 'integer',
            'attr' => ['label' => $this->addCurrencySuffix('Total Award Amount')],
            'after' => 'maximum_award_amount'
        ]);
        $this->field('duration', [
            'type' => 'select',
            'attr' => ['label' => __('Duration (Year)')],
            'after' => 'total_amount'
        ]);
        $this->field('bond', [
            'type' => 'select',
            'attr' => ['label' => __('Bond (Year)')],
            'after' => 'duration'
        ]);
        $this->field('attachment_type_id', [
            'type' => 'custom_attachment_type',
            'after' => 'instructions'
        ]);
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

    public function getBondOptions($maxYears)
    {
        $bondOptions = [];

        for ($i=0; $i<=$maxYears; $i++) {
            $bondOptions[] = $i . ' ';
        }

        return $bondOptions;
    }

    public function getDurationOptions($maxYears)
    {
        $durationOptions = [];

        for ($i=0; $i<=$maxYears; $i++) {
            $durationOptions[] = $i . ' ';
        }

        return $durationOptions;
    }

    public function getLoanTermOptions($maxYears)
    {
        $loanTermOptions = [];

        for ($i=1; $i<=$maxYears; $i++) {
            $loanTermOptions[] = $i;
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

    public function getUsedAttachmentTypes($scholarshipId)
    {
        $types = $this->AttachmentTypes->find()
            ->innerJoinWith('ApplicationAttachments', function ($q) use ($scholarshipId) {
                return $q->where(['ApplicationAttachments.scholarship_id' => $scholarshipId]);
            })
            ->extract('id')
            ->toArray();
        return $types;
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
                    $this->aliasField('application_open_date <=') => $todayDate,
                    $this->aliasField('application_close_date >=') => $todayDate
                ])
                ->toArray();
        }
        return $availableScholarships;
    }
}
