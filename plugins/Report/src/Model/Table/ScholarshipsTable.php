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

class ScholarshipsTable extends AppTable  {

    use OptionsTrait;

    private $interestRateOptions = [];

    public function initialize(array $config) {
        
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

        $this->interestRateOptions = $this->getSelectOptions($this->aliasField('interest_rate'));
       
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

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) 
    {
        $attr['options'] = $this->AcademicPeriods->getYearList();
        $attr['default'] = $this->AcademicPeriods->getCurrent();
        
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if ($feature == 'Report.RecipientPaymentStructures') {
                $attr['type'] = 'hidden';
            } 
        }
        return $attr;
    }

    public function onUpdateFieldScholarshipFinancialAssistanceTypeId(Event $event, array $attr, $action, Request $request) 
    {
        $financialAssistanceTypeOptions = $this->FinancialAssistanceTypes->getList()->toArray();
        $financialAssistanceTypeOptions = ['-1' => __('All Types')] + $financialAssistanceTypeOptions;

        $attr['type'] = 'select';
        $attr['select'] = false;
        $attr['attr']['label'] = __('Financial Assistance Type');
        $attr['options'] = $financialAssistanceTypeOptions;

        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if ($feature == 'Report.RecipientPaymentStructures') {
                $attr['type'] = 'hidden';
            } 
        }
        return $attr;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $financialAssistanceType = $requestData->scholarship_financial_assistance_type_id;

        $conditions = [
            $this->aliasField('academic_period_id') => $academicPeriodId
        ];

        if ($financialAssistanceType != -1) {
            $conditions[$this->aliasField('scholarship_financial_assistance_type_id')] = $financialAssistanceType;
        }

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
                ]
            ])
            ->select([
                'interest_rate' => 'Loans.interest_rate', 
                'interest_rate_type' => 'Loans.interest_rate_type', 
                'loan_term' => 'Loans.loan_term', 
                'payment_frequency_name' => 'PaymentFrequencies.name'
            ])
            ->where($conditions); 
    }
    
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {       
        $newArray = [];
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
   
        $newFields = array_merge($fields->getArrayCopy(), $newArray);
        $fields->exchangeArray($newFields);
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
}
