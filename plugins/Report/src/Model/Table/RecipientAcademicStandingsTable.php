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

class RecipientAcademicStandingsTable extends AppTable  {

    public function initialize(array $config) {
        
        $this->table('scholarship_recipient_academic_standings');
        parent::initialize($config);
        
        $this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Semesters', ['className' => 'Scholarship.Semesters', 'foreignKey' => 'scholarship_semester_id']);
        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
       
        $this->addBehavior('Excel', ['pages' => false]);
        $this->addBehavior('Report.ReportList');
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
                        'gender_id'
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
                'AcademicPeriods' => [
                    'fields' => [
                        'code',
                        'name'
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
                'Semesters' => [
                    'fields' => [
                        'name'
                    ]
                ],
            ])
            ->select([
                'openemis_no' => 'Recipients.openemis_no',
                'gender_name' => 'Genders.name',
                'financial_assistance_type' => 'FinancialAssistanceTypes.name'
            ])
            ->where([$conditions]); 
    }
    
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {       
        $newArray = [];
        $newArray[] = [
            'key' => 'Recipients.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' =>  'OpenEMIS ID'
        ];
        $newArray[] = [
            'key' => 'RecipientAcademicStandings.recipient_id',
            'field' => 'recipient_id',
            'type' => 'integer',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Recipients.gender_id',
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
            'key' => 'RecipientAcademicStandings.scholarship_id',
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
            'key' => 'RecipientAcademicStandings.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newArray[] = [
            'key' => 'RecipientAcademicStandings.scholarship_semester_id',
            'field' => 'scholarship_semester_id',
            'type' => 'integer',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'RecipientAcademicStandings.date',
            'field' => 'date',
            'type' => 'date',
            'label' => __('Date Entered')
        ];
        $newArray[] = [
            'key' => 'RecipientAcademicStandings.gpa',
            'field' => 'gpa',
            'type' => 'string',
            'label' => __('Student GPA')
        ];
        $newArray[] = [
            'key' => 'RecipientAcademicStandings.comments',
            'field' => 'comments',
            'type' => 'string',
            'label' => ''
        ];
   
        $fields->exchangeArray($newArray);
    }
}
