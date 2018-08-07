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

class ScholarshipApplicationsTable extends AppTable  {

    use OptionsTrait;

    private $interestRateOptions = [];

    public function initialize(array $config) {
        
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
                'Applicants' => [
                    'fields' => [
                        'id',
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name',
                        'gender_id'
                    ]
                ],
                'Applicants.Genders' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'name',
                        'maximum_award_amount',
                        'bond',
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
                'Statuses' => [
                    'fields' => [
                        'name'
                    ]
                ],
            ])
            ->select([
                'openemis_no' => 'Applicants.openemis_no',
                'gender_name' => 'Genders.name',
                'academic_period_id' => 'AcademicPeriods.name',
                'financial_assistance_type' => 'FinancialAssistanceTypes.name',
                'maximum_award_amount' => 'Scholarships.maximum_award_amount',
                'bond' => 'Scholarships.bond'
            ])
            ->where($conditions); 

    }
    
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {       
        $newArray = [];
        $newArray[] = [
            'key' => 'Scholarships.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'ScholarshipApplications.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Applicants.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' =>  ''
        ];
        $newArray[] = [
            'key' => 'ScholarshipApplications.applicant_id',
            'field' => 'applicant_id',
            'type' => 'integer',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Applicants.gender_id',
            'field' => 'gender_name',
            'type' => 'string',
            'label' =>  ''
        ];
        $newArray[] = [
            'key' => 'ScholarshipApplications.scholarship_id',
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
            'key' => 'Scholarships.maximum_award_amount',
            'field' => 'maximum_award_amount',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Scholarships.bond',
            'field' => 'bond',
            'type' => 'string',
            'label' => __('Bond (Years)')
        ];
   
        $fields->exchangeArray($newArray);
    }
}
