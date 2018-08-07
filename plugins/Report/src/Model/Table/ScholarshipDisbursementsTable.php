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

        $conditions = [];

        if ($financialAssistanceType != -1) {
            $conditions[$this->aliasField('scholarship_recipient_activity_status_id')] = $financialAssistanceType;
        }

        $query->where('AcademicPeriods.id = '.$academicPeriodId);

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
            ->matching('RecipientPaymentStructureEstimates', function ($q) {
                return $q->select([
                    'estimated_amount' => 'SUM(RecipientPaymentStructureEstimates.estimated_amount)'
                ]);
            })
            ->matching('RecipientDisbursements', function ($q) {
                return $q->select([
                    'total_disbursed_amount' => 'SUM(RecipientDisbursements.amount)'
                ]);
            })
            ->select([
                $this->aliasField('recipient_id'),
                'recipient_scholarship_id' => $this->aliasField('scholarship_id'),
                'recipients_openemis_no' => 'Recipients.openemis_no',
                'recipients_geneder' => 'Genders.name',
                'main_nationality' => 'MainNationalities.name',
                'identity_type' => 'IdentityTypes.name',
                'scholarship_award' => 'Scholarships.name',
                'approved_amount' => $this->aliasField('approved_amount'),
                'outstanding_amount' => $query->newExpr('SUM(RecipientPaymentStructureEstimates.estimated_amount - RecipientDisbursements.amount)')
            ])
            ->where($conditions)
                ->group([
                'RecipientPaymentStructureEstimates.recipient_id', 
                'RecipientPaymentStructureEstimates.scholarship_id'
                ]);

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
            'label' =>  __('Student')
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
            'key' => 'scholarship_award',
            'field' => 'scholarship_award',
            'type' => 'string',
            'label' =>  __('Scholarships')
        ];
        $newArray[] = [
            'key' => 'approved_amount',
            'field' => 'approved_amount',
            'type' => 'decimal',
            'label' =>  __('Approved Amount')
        ];
        $newArray[] = [
            'key' => 'estimated_amount',
            'field' => 'estimated_amount',
            'type' => 'decimal',
            'label' =>  __('Estimated Amount')
        ];
        $newArray[] = [
            'key' => 'total_disbursed_amount',
            'field' => 'total_disbursed_amount',
            'type' => 'decimal',
            'label' =>  __('Total Disbursed Amount')
        ];
        $newArray[] = [
            'key' => 'outstanding_amount',
            'field' => 'outstanding_amount',
            'type' => 'decimal',
            'label' =>  __('Outstanding Amount')
        ];
   
        $newFields = array_merge($fields->getArrayCopy(), $newArray);
        $fields->exchangeArray($newArray);
    }
}
