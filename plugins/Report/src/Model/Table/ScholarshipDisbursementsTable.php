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
use Cake\Datasource\ResultSetInterface;

class ScholarshipDisbursementsTable extends AppTable  {

    use OptionsTrait;

    private $interestRateOptions = [];

    public function initialize(array $config) {
        
        $this->table('scholarship_recipients');
        parent::initialize($config);
        
        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('RecipientActivityStatuses', ['className' => 'Scholarship.RecipientActivityStatuses', 'foreignKey' => 'scholarship_recipient_activity_status_id']);
        $this->hasMany('RecipientAcademicStandings', [
            'className' => 'Scholarship.RecipientAcademicStandings',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('RecipientActivities', [
            'className' => 'Scholarship.RecipientActivities',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true,
            'saveStrategy' => 'append'
        ]);
        $this->hasMany('RecipientCollections', [
            'className' => 'Scholarship.RecipientCollections',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('RecipientDisbursements', [
            'className' => 'Scholarship.RecipientDisbursements',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('RecipientPaymentStructureEstimates', [
            'className' => 'Scholarship.RecipientPaymentStructureEstimates',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('RecipientPaymentStructures', [
            'className' => 'Scholarship.RecipientPaymentStructures',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Excel', ['pages' => false]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event) 
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['select' => false]);
        $this->ControllerAction->field('scholarship_financial_assistance_type_id');
    }
    
    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $financialAssistanceType = $requestData->scholarship_financial_assistance_type_id;

        $recipientList = [];
        $recipientScholarshipResult = $this
            ->find('list', [
                'keyField' => 'recipient_id',
                'valueField' => 'scholarship_id'
            ])
            ->select([
                'recipient_id' => $this->aliasField('recipient_id'),
                'scholarship_id' => $this->aliasField('scholarship_id')
            ])
            ->all();

        if (!$recipientScholarshipResult->isEmpty()) {
            $resultSet = $recipientScholarshipResult->toArray();

            foreach ($resultSet as $recipientId => $scholarshipId) {
                if (!isset($recipientList[$recipientId])) {
                    $recipientList[$recipientId] = [];
                }
    
                if (!isset($recipientList[$recipientId][$scholarshipId])) {
                    $recipientList[$recipientId][$scholarshipId] = [
                        'estimated_amount' => NULL,
                        'total_disbursement' => NULL
                    ];
                }
            }
        }

        if (!empty($recipientList)) {
            $paymentStructuresQuery = $this->RecipientDisbursements->find();
            $paymentStructuresResult = $paymentStructuresQuery
                ->select([
                    $this->RecipientDisbursements->aliasField('recipient_id'),
                    $this->RecipientDisbursements->aliasField('scholarship_id'),
                    'total_disbursement' => $paymentStructuresQuery->func()->sum('amount'),
                    'RecipientPaymentStructures.name'
                ])

                ->contain([
                    'RecipientPaymentStructures'
                ])

                ->group([
                    $this->RecipientDisbursements->aliasField('scholarship_recipient_payment_structure_id'),             
                ])
                ->all();

            $disbursementQuery = $this->RecipientDisbursements->find();
            $disbursementResult = $disbursementQuery
                ->select([
                    $this->RecipientDisbursements->aliasField('recipient_id'),
                    $this->RecipientDisbursements->aliasField('scholarship_id'),
                    'total_disbursement' => $disbursementQuery->func()->sum('amount')
                ])
                ->group([
                    $this->RecipientDisbursements->aliasField('recipient_id'),
                    $this->RecipientDisbursements->aliasField('scholarship_id')
                ])
                ->all();

            if (!$disbursementResult->isEmpty()) {
                $resultSet = $disbursementResult->toArray();

                foreach ($resultSet as $entity) {
                    $recipientId = $entity->recipient_id;
                    $scholarshipId = $entity->scholarship_id;

                    $recipientList[$recipientId][$scholarshipId]['total_disbursement'] = $entity->total_disbursement;
                }
            }

            $structureEstimateQuery = $this->RecipientPaymentStructureEstimates->find();
            $structureEstimateResult = $structureEstimateQuery
                ->select([
                    $this->RecipientPaymentStructureEstimates->aliasField('recipient_id'),
                    $this->RecipientPaymentStructureEstimates->aliasField('scholarship_id'),
                    'estimated_amount' => $structureEstimateQuery->func()->sum('estimated_amount')  
                ])
                ->group([
                    $this->RecipientPaymentStructureEstimates->aliasField('recipient_id'),
                    $this->RecipientPaymentStructureEstimates->aliasField('scholarship_id'),
                ])
                ->all();

            if (!$structureEstimateResult->isEmpty()) {
                $resultSet = $structureEstimateResult->toArray();

                foreach ($resultSet as $entity) {
                    $recipientId = $entity->recipient_id;
                    $scholarshipId = $entity->scholarship_id;

                    $recipientList[$recipientId][$scholarshipId]['estimated_amount'] = $entity->estimated_amount;
                }
            }
        }
        

        $conditions = [];
        if ($financialAssistanceType != -1) {
            $conditions['Scholarships.scholarship_financial_assistance_type_id'] = $financialAssistanceType;
        }  

        $query
            ->contain([
                'Recipients' => [
                    'fields' => [
                        'Recipients.openemis_no',
                        'Recipients.first_name',
                        'Recipients.middle_name',
                        'Recipients.third_name',
                        'Recipients.last_name',
                        'Recipients.preferred_name',
                        'Recipients.gender_id'
                    ]
                ],
                'Recipients.Genders' => [
                    'fields' => [
                        'Genders.name'
                    ]
                ],
                'Recipients.MainNationalities' => [
                    'fields' => [
                        'MainNationalities.name'
                    ]
                ],
                'Recipients.MainNationalities.IdentityTypes' => [
                    'fields' => [
                        'IdentityTypes.name'
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'Scholarships.code',
                        'Scholarships.name',
                    ]
                ],
                'Scholarships.AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.code',
                        'AcademicPeriods.name',
                    ]
                ],
                'Scholarships.FinancialAssistanceTypes' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ]
            ])
            ->select([
                $this->aliasField('recipient_id'),
                $this->aliasField('scholarship_id'),
                'recipients_openemis_no' => 'Recipients.openemis_no',
                'recipients_identity_number' => 'Recipients.identity_number',
                'recipients_geneder' => 'Genders.name',
                'main_nationality' => 'MainNationalities.name',
                'identity_type' => 'IdentityTypes.name',
                'scholarship_award' => 'Scholarships.name',
                'approved_amount' => $this->aliasField('approved_amount'),
            ])
            ->where($conditions)
            ->formatResults(function (ResultSetInterface $results) use ($recipientList, $paymentStructuresResult) {
                $finalResultSet = [];
                $resultSet =  $results->map(function ($row) use ($recipientList, $paymentStructuresResult) {
                    $recipientId = $row->recipient_id;
                    $scholarshipId = $row->scholarship_id;

                    if (!empty($recipientList) && isset($recipientList[$recipientId]) && isset($recipientList[$recipientId][$scholarshipId])) {
                            $amount = $recipientList[$recipientId][$scholarshipId];
                            $row->estimated_amount = $amount['estimated_amount'];
                            $row->total_disbursement = $amount['total_disbursement'];
                            $row->outstanding_amount = ($row->approved_amount - $amount['total_disbursement']);
                    }

                    return $row;
                });

                $arrayPaymentStructureResult = $paymentStructuresResult->toArray();
                foreach ($resultSet as $singleSet) {
                    $hasPaymentStructure = false;

                    foreach ($arrayPaymentStructureResult as $paymentStrucureRow) {
                        if ($paymentStrucureRow->recipient_id == $singleSet->recipient_id
                            && $paymentStrucureRow->scholarship_id == $singleSet->scholarship_id 
                            && !isset($paymentStrucureRow->hasUsed)) {
                            $recipientPaymentStructure = $paymentStrucureRow->recipient_payment_structure;
                            $singleSet->payment_structure = $recipientPaymentStructure->name;
                            $singleSet->disbursement_amount = $paymentStrucureRow->total_disbursement;

                            $finalResultSet[] = clone $singleSet;
                            $paymentStrucureRow->hasUsed = true;
                            $hasPaymentStructure = true;
                        }
                    }

                    if (!$hasPaymentStructure) {
                        $finalResultSet[] = $singleSet;
                    }
                }
                return $finalResultSet;
           });
    }
    
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {       
        $newArray = [];
        $newArray[] = [
            'key' => 'recipients_openemis_no',
            'field' => 'recipients_openemis_no',
            'type' => 'string',
            'label' =>  __('OpenEMIS ID')
        ];
        $newArray[] = [
            'key' => 'recipient.id',
            'field' => 'recipient_id',
            'type' => 'string',
            'label' =>  __('Recipient')
        ];
        $newArray[] = [
            'key' => 'recipients_geneder',
            'field' => 'recipients_geneder',
            'type' => 'string',
            'label' =>  __('Gender')
        ];
        $newArray[] = [
            'key' => 'main_nationality',
            'field' => 'main_nationality',
            'type' => 'string',
            'label' =>  __('Nationality')
        ];
        $newArray[] = [
            'key' => 'identity_type',
            'field' => 'identity_type',
            'type' => 'string',
            'label' =>  __('Identity Type')
        ];
        $newArray[] = [
            'key' => 'recipients_identity_number',
            'field' => 'recipients_identity_number',
            'type' => 'string',
            'label' =>  __('Identity Number')
        ];
        $newArray[] = [
            'key' => 'scholarship_award',
            'field' => 'scholarship_award',
            'type' => 'string',
            'label' =>  __('Scholarships')
        ];
        $newArray[] = [
            'key' => 'approved_amount',
            'field' => 'approved_amount',
            'type' => 'decimal',
            'label' =>  __('Approved Award Amount')
        ];
        $newArray[] = [
            'key' => 'estimated_amount',
            'field' => 'estimated_amount',
            'type' => 'decimal',
            'label' =>  __('Estimated Amount')
        ];
        $newArray[] = [
            'key' => 'total_disbursement',
            'field' => 'total_disbursement',
            'type' => 'decimal',
            'label' =>  __('Total Disbursed Amount')
        ];
        $newArray[] = [
            'key' => 'outstanding_amount',
            'field' => 'outstanding_amount',
            'type' => 'decimal',
            'label' =>  __('Outstanding Amount')
        ];
        $newArray[] = [
            'key' => 'payment_structure',
            'field' => 'payment_structure',
            'type' => 'string',
            'label' =>  __('Payment Structure')
        ];
        $newArray[] = [
            'key' => 'disbursementAmount',
            'field' => 'disbursement_amount',
            'type' => 'string',
            'label' =>  __('Disbursement Amount')
        ];        
   
        $newFields = array_merge($fields->getArrayCopy(), $newArray);
        $fields->exchangeArray($newArray);
    }
}
