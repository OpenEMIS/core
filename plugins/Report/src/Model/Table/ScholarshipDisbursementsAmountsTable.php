<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class ScholarshipDisbursementsAmountsTable extends AppTable  {

    use OptionsTrait;

    public function initialize(array $config) 
    {
        
        $this->table('scholarship_recipient_disbursements');
        parent::initialize($config);

        $this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
        $this->belongsTo('Semesters', ['className' => 'Scholarship.Semesters', 'foreignKey' => 'scholarship_semester_id']);
        $this->belongsTo('DisbursementCategories', ['className' => 'Scholarship.DisbursementCategories', 'foreignKey' => 'scholarship_disbursement_category_id']);
        $this->belongsTo('RecipientPaymentStructures', ['className' => 'Scholarship.RecipientPaymentStructures', 'foreignKey' => 'scholarship_recipient_payment_structure_id']);
        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Excel', ['pages' => false]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelGetGender(Event $event, Entity $entity)
    {
        $gender = '';
        if (!is_null($entity->recipient->gender->name)) {
            $gender = $entity->recipient->gender->name;
        }

        return $gender;
    }

    public function onExcelGetNationality(Event $event, Entity $entity)
    {
        $nationality = '';
        if (!is_null($entity->recipient->main_nationality->name)) {
            $nationality = $entity->recipient->main_nationality->name;
        }

        return $nationality;
    }

    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $identityType = '';
        if (!is_null($entity->recipient->main_identity_type->name)) {
            $identityType = $entity->recipient->main_identity_type->name;
        }

        return $identityType;
    }

    public function onExcelGetAcademicPeriods(Event $event, Entity $entity)
    {   
        $academicPeriods = '';
        if (!is_null($entity->academic_periods)) {
            $academicPeriods = $entity->academic_periods;
        }

        return $academicPeriods;
    }

    public function onExcelGetEstimatedAmount(Event $event, Entity $entity)
    {
        $estimatedAmount = '';

        $estimatesStructure = TableRegistry::get('Scholarship.RecipientPaymentStructureEstimates');
        $result = $estimatesStructure
            ->find()
            ->where([
                $estimatesStructure->aliasField('scholarship_recipient_payment_structure_id') => $entity->scholarship_recipient_payment_structure_id,
                $estimatesStructure->aliasField('recipient_id') => $entity->recipient_id,
            ]) 
            ->select([
            'estimated_amount' => $estimatesStructure->find()->func()->sum('estimated_amount')  
                ])
            ->all();

            if (!$result->isEmpty()) {
                $estimatedAmount = $result->first()->estimated_amount;
            }

        return $estimatedAmount;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $financialAssistanceType = $requestData->scholarship_financial_assistance_type_id;

        $conditions = [
            $this->Scholarships->aliasField('academic_period_id') => $academicPeriodId
        ];

        if ($financialAssistanceType != -1) {
            $conditions[$this->Scholarships->aliasField('scholarship_financial_assistance_type_id')] = $financialAssistanceType;
        }

       $query
            ->contain([
                'Recipients' => [
                    'fields' => [
                        'openemis_no' => 'Recipients.openemis_no',
                        'Recipients.first_name',
                        'Recipients.middle_name',
                        'Recipients.third_name',
                        'Recipients.last_name',
                        'Recipients.preferred_name',
                        'gender_id',
                        'nationality_id' => 'Recipients.nationality_id',
                        'identity_type_id' => 'Recipients.identity_type_id',
                        'identity_number' => 'Recipients.identity_number',
                    ]
                ],
                'Recipients.Genders' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Recipients.MainNationalities' => [
                    'fields' => [
                        'id',
                        'name'
                    ]
                ],
                'Recipients.MainIdentityTypes' => [
                    'fields' => [
                        'id',
                        'name'
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'name',
                    ]
                ],
                'DisbursementCategories' => [
                    'fields' => [
                        'id',
                        'name'
                    ]
                ],
                'Semesters' => [
                    'fields' => [
                        'id',
                        'name'
                    ]
                ],
                'RecipientPaymentStructures' => [
                    'fields' => [
                        'id',
                        'name'
                    ]
                ],                
                'RecipientPaymentStructures.AcademicPeriods' => [
                    'fields' => [
                        'id',
                        'academic_periods'  => 'AcademicPeriods.name'
                    ]
                ],
            ])
            ->select([
                $this->aliasField('recipient_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('scholarship_disbursement_category_id'),
                $this->aliasField('scholarship_semester_id'),
                $this->aliasField('disbursement_date'),
                $this->aliasField('amount'),
                $this->aliasField('comments'),
                $this->aliasField('scholarship_recipient_payment_structure_id'),
            ])
            ->order([
                $this->aliasField('recipient_id'),
                $this->aliasField('disbursement_date'),
            ])
            ->where($conditions);

    }

   public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {       
       $newFields = [];

        $newFields[] = [
            'key' => 'Recipients.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $newFields[] = [
            'key' => 'Recipients.recipient_id',
            'field' => 'recipient_id',
            'type' => 'string',
            'label' => __('Recipient')
        ];

        $newFields[] = [
            'key' => 'Recipients.gender_id',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];
        
        $newFields[] = [
            'key' => 'Recipients.nationality_id',
            'field' => 'nationality',
            'type' => 'string',
            'label' => __('Nationality')
        ];

        $newFields[] = [
            'key' => 'Recipients.identity_type_id',
            'field' => 'identityType',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $newFields[] = [
            'key' => 'Recipients.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $newFields[] = [
            'key' => 'ScholarshipApplications.scholarship_id',
            'field' => 'scholarship_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academicPeriods',
            'type' => 'string',
            'label' => __('Academic Periods')
        ];

        $newFields[] = [
            'key' => 'scholarship_recipient_payment_structure',
            'field' => 'scholarship_recipient_payment_structure_id',
            'type' => 'integer',
            'label' => __('Payment Structure')
        ];          

        $newFields[] = [
            'key' => 'EstimatedAmount',
            'field' => 'estimatedAmount',
            'type' => 'string',
            'label' => __('Estimated Amount')
        ];

        $newFields[] = [
            'key' => 'DisbursementCategories',
            'field' => 'scholarship_disbursement_category_id',
            'type' => 'integer',
            'label' => __('Disbursement Categories')
        ];

        $newFields[] = [
            'key' => 'DisbursementDate',
            'field' => 'disbursement_date',
            'type' => 'date',
            'label' => __('Disbursement Date')
        ];

        $newFields[] = [
            'key' => 'Amount',
            'field' => 'amount',
            'type' => 'integer',
            'label' => __('Disbursement Amount')
        ];

        $newFields[] = [
            'key' => 'Semesters',
            'field' => 'scholarship_semester_id',
            'type' => 'integer',
            'label' => __('Semesters')
        ];

        $newFields[] = [
            'key' => 'Comments',
            'field' => 'comments',
            'type' => 'string',
            'label' => __('Comments')
        ];
        $fields->exchangeArray($newFields);
    }
}
