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

class ScholarshipDisbursementsAmountsTable extends AppTable
{

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
        $this->addBehavior('Report.ReportList');
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

        $conditions = [];
        $conditions[] = [
            $this->Scholarships->aliasField('academic_period_id') => $academicPeriodId
        ];

        if ($financialAssistanceType != -1) {
            $conditions[] = [
                $this->Scholarships->aliasField('scholarship_financial_assistance_type_id') => $financialAssistanceType
            ];
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
                        'identity_number' => 'Recipients.identity_number',
                    ]
                ],
                'Recipients.Genders' => [
                    'fields' => [
                        'gender' => 'Genders.name'
                    ]
                ],
                'Recipients.MainNationalities' => [
                    'fields' => [
                        'nationality_name' => 'MainNationalities.name',
                    ]
                ],
                'Recipients.MainIdentityTypes' => [
                    'fields' => [
                        'identity_type_name' => 'MainIdentityTypes.name',
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'name',
                    ]
                ],
                'DisbursementCategories' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Semesters' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'RecipientPaymentStructures' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'RecipientPaymentStructures.AcademicPeriods' => [
                    'fields' => [
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
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => __('Nationality')
        ];

        $newFields[] = [
            'key' => 'Recipients.identity_type_id',
            'field' => 'identity_type_name',
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
            'key' => 'scholarship_recipient_payment_structure',
            'field' => 'scholarship_recipient_payment_structure_id',
            'type' => 'integer',
            'label' => __('Payment Structure')
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
            'label' => __('Disbursed Amount')
        ];

        $newFields[] = [
            'key' => 'EstimatedAmount',
            'field' => 'estimatedAmount',
            'type' => 'string',
            'label' => __('Estimated Amount')
        ];

        $newFields[] = [
            'key' => 'Semesters',
            'field' => 'scholarship_semester_id',
            'type' => 'integer',
            'label' => __('Semester')
        ];

        $newFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_periods',
            'type' => 'string',
            'label' => __('Academic Period')
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
