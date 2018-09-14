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

class RecipientPaymentStructuresTable extends AppTable  {

    public function initialize(array $config) {
        
        $this->table('scholarship_recipient_payment_structures');
        parent::initialize($config);
       
        $this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->hasMany('RecipientPaymentStructureEstimates', ['className' => 'Scholarship.RecipientPaymentStructureEstimates', 'foreignKey' => 'scholarship_recipient_payment_structure_id', 'dependent' => true, 'cascadeCallbacks' => true,  'saveStrategy' => 'replace']);
        $this->hasMany('RecipientDisbursements', ['className' => 'Scholarship.RecipientDisbursements', 'foreignKey' => 'scholarship_recipient_payment_structure_id', 'dependent' => true, 'cascadeCallbacks' => true,  'saveStrategy' => 'replace']);
          
        $this->addBehavior('Excel', ['pages' => false]);
        $this->addBehavior('Report.ReportList');
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
                        'id',
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name',
                        'gender_id',
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
                        'scholarship_financial_assistance_type_id',
                    ]
                ],
                'Scholarships.FinancialAssistanceTypes' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'AcademicPeriods' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
            ])
            ->matching('RecipientPaymentStructureEstimates.DisbursementCategories')
            ->select([
                'openemis_no' => 'Recipients.openemis_no',
                'gender_name' => 'Genders.name',
                'financial_assistance_type' => 'FinancialAssistanceTypes.name',
                'estimated_disbursement_date' => 'RecipientPaymentStructureEstimates.estimated_disbursement_date',
                'estimated_amount' => 'RecipientPaymentStructureEstimates.estimated_amount',
                'category_name' => 'DisbursementCategories.name'
            ])
            ->where($conditions)
            ->order([$this->aliasField('recipient_id'), $this->aliasField('scholarship_id'), $this->aliasField('id')]);
    }
    
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {       
        $newArray = [];
        $newArray[] = [
            'key' => 'Recipients.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' =>  __('OpenEMIS ID')
        ];
        $newArray[] = [
            'key' => 'RecipientPaymentStructures.recipient_id',
            'field' => 'recipient_id',
            'type' => 'integer',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Applicant.gender_id',
            'field' => 'gender_name',
            'type' => 'string',
            'label' =>  ''
        ];
        $newArray[] = [
            'key' => 'Recipients.nationality_id',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => __('Nationality')
        ];
        $newArray[] = [
            'key' => 'Recipients.identity_type_id',
            'field' => 'identity_type_name',
            'type' => 'string',
            'label' => __('Identity Type')
        ];
        $newArray[] = [
            'key' => 'Recipients.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $newArray[] = [
            'key' => 'RecipientPaymentStructures.scholarship_id',
            'field' => 'scholarship_id',
            'type' => 'integer',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Scholarships.scholarship_financial_assistance_type_id',
            'field' => 'financial_assistance_type',
            'type' => 'string',
            'label' => __('Financial Assistance Type')
        ];

        $newArray[] = [
            'key' => 'RecipientPaymentStructures.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newArray[] = [
            'key' => 'RecipientPaymentStructures.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Payment Structure')
        ];

        $newArray[] = [
            'key' => 'RecipientPaymentStructureEstimates.scholarship_disbursement_category_id',
            'field' => 'category_name',
            'type' => 'string',
            'label' =>  __('Category')
        ];

        $newArray[] = [
            'key' => 'RecipientPaymentStructureEstimates.estimated_disbursement_date',
            'field' => 'estimated_disbursement_date',
            'type' => 'date',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'RecipientPaymentStructureEstimates.estimated_amount',
            'field' => 'estimated_amount',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'total_estimated_amounts',
            'field' => 'total_estimated_amounts',
            'type' => 'string',
            'label' => __('Total Estimated Amounts')
        ];
   
        $fields->exchangeArray($newArray);
    }

    public function onExcelGetTotalEstimatedAmounts(Event $event, Entity $entity)
    {
        $value = $this->RecipientPaymentStructureEstimates->getEstimatedAmount($entity->id);
        return $value;
    }
}
