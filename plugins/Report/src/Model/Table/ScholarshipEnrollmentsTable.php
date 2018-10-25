<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class ScholarshipEnrollmentsTable extends AppTable
{

    use OptionsTrait;

    public function initialize(array $config) 
    {
        $this->table('scholarship_application_institution_choices');
        parent::initialize($config);

        $this->belongsTo('Applications', ['className' => 'Scholarship.Applications', 'foreignKey' => ['applicant_id', 'scholarship_id']]);
        $this->belongsTo('Countries', ['className' => 'FieldOption.Countries', 'foreignKey' => 'country_id']);
        $this->belongsTo('InstitutionChoiceTypes', ['className' => 'Scholarship.InstitutionChoiceTypes', 'foreignKey' => 'scholarship_institution_choice_type_id']);
        $this->belongsTo('InstitutionChoiceStatuses', ['className' => 'Scholarship.InstitutionChoiceStatuses', 'foreignKey' => 'scholarship_institution_choice_status_id']);
        $this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies' , 'foreignKey' => 'education_field_of_study_id']);
        $this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels',  'foreignKey' =>'qualification_level_id' ]);
        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);

        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $RecipientAcademicStandings = TableRegistry::get('Scholarship.RecipientAcademicStandings');
        $Semesters = TableRegistry::get('Scholarship.Semesters');
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $financialAssistanceType = $requestData->scholarship_financial_assistance_type_id;

        $conditions = [];
        $conditions[] = [
            $this->Scholarships->aliasField('academic_period_id') => $academicPeriodId
        ];

        $conditions[] = [
            $this->aliasField('is_selected') => 1
        ];        

        if ($financialAssistanceType != -1) {
            $conditions[] = [
                $this->Scholarships->aliasField('scholarship_financial_assistance_type_id') => $financialAssistanceType
            ];
        }

       $query
            ->contain([
                'Applicants' => [
                    'fields' => [
                        'openemis_no' => 'Applicants.openemis_no',
                        'email' => 'Applicants.email',
                        'Applicants.first_name',
                        'Applicants.middle_name',
                        'Applicants.third_name',
                        'Applicants.last_name',
                        'Applicants.preferred_name',
                        'identity_number' => 'Applicants.identity_number',
                    ]
                ],
                'Applicants.Genders' => [
                      'fields' => [
                        'gender' => 'Genders.name'
                    ]
                ],
                'Applicants.MainNationalities' => [
                      'fields' => [
                        'nationality_name' => 'MainNationalities.name',
                    ]
                ],
                'Applicants.MainIdentityTypes' => [
                    'fields' => [
                        'identity_type_name' => 'MainIdentityTypes.name',
                    ]
                ],
                'InstitutionChoiceTypes' => [
                    'fields' => [
                        'institution_name' => 'InstitutionChoiceTypes.name',
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'name',
                    ]
                ],
                'Countries' => [
                    'fields' => [
                        'name',
                    ]
                ],  
                'EducationFieldOfStudies' => [
                    'fields' => [
                        'name',
                    ]
                ],      
                'QualificationLevels' => [
                    'fields' => [
                        'name',
                    ]
                ],
                'InstitutionChoiceStatuses' => [
                    'fields' => [
                        'name' => 'InstitutionChoiceStatuses.name',
                    ]
                ],
            ])
            ->leftJoin([$RecipientAcademicStandings->alias() => $RecipientAcademicStandings->table()], [
                $RecipientAcademicStandings->aliasField('recipient_id'). ' = ' .$this->aliasField('applicant_id'),
                $RecipientAcademicStandings->aliasField('scholarship_id'). ' = ' .$this->aliasField('scholarship_id'),
            ])
            ->leftJoin([$Semesters->alias() => $Semesters->table()], [
                $RecipientAcademicStandings->aliasField('scholarship_semester_id'). ' = ' .$Semesters->aliasField('id'),
            ])
            ->where([
                $conditions
            ])
            ->select([
                $this->aliasField('applicant_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('location_type'),
                $this->aliasField('course_name'),
                $this->aliasField('start_date'),
                $this->aliasField('end_date'),
                $this->aliasField('country_id'),
                $this->aliasField('education_field_of_study_id'),
                $this->aliasField('qualification_level_id'),
                'gpa' => $RecipientAcademicStandings->aliasField('gpa'),
                'semesterName' => $Semesters->aliasField('name'),
            ]);
    }

   public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
   {
       $newFields = [];
        $newFields[] = [
            'key' => 'Applicants.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $newFields[] = [
            'key' => 'Applicants.recipient_id',
            'field' => 'applicant_id',
            'type' => 'string',
            'label' => __('Recipient')
        ];

        $newFields[] = [
            'key' => 'Applicants.gender_id',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];
        
        $newFields[] = [
            'key' => 'Applicants.nationality_id',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => __('Nationality')
        ];

        $newFields[] = [
            'key' => 'Applicants.identity_type_id',
            'field' => 'identity_type_name',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $newFields[] = [
            'key' => 'Applicants.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $newFields[] = [
            'key' => 'Applicants.email',
            'field' => 'email',
            'type' => 'string',
            'label' => __('Email')
        ];

        $newFields[] = [
            'key' => 'ScholarshipApplications.scholarship_id',
            'field' => 'scholarship_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'location_type',
            'field' => 'location_type',
            'type' => 'string',
            'label' => __('Location Type')
        ];

        $newFields[] = [
            'key' => 'country_id',
            'field' => 'country_id',
            'type' => 'string',
            'label' => __('Country')
        ];

        $newFields[] = [
            'key' => 'InstitutionChoiceTypes.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution')
        ];        

        $newFields[] = [
            'key' => 'education_field_of_study_id',
            'field' => 'education_field_of_study_id',
            'type' => 'string',
            'label' => __('Area of Study')
        ];

        $newFields[] = [
            'key' => 'course_name',
            'field' => 'course_name',
            'type' => 'string',
            'label' => __('Course')
        ];        

        $newFields[] = [
            'key' => 'qualification_level_id',
            'field' => 'qualification_level_id',
            'type' => 'string',
            'label' => __('Qualification Level')
        ];

        $newFields[] = [
            'key' => 'StartDate',
            'field' => 'start_date',
            'type' => 'date',
            'label' => __('Commencement Date')
        ];

        $newFields[] = [
            'key' => 'EndDate',
            'field' => 'end_date',
            'type' => 'date',
            'label' => __('Completion Date')
        ];

        $newFields[] = [
            'key' => 'InstitutionChoiceStatuses.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Status')
        ];

        $newFields[] = [
            'key' => 'gpa',
            'field' => 'gpa',
            'type' => 'string',
            'label' => __('GPA')
        ];

        $newFields[] = [
            'key' => 'semesterName',
            'field' => 'semesterName',
            'type' => 'string',
            'label' => __('Semester')
        ];

        $fields->exchangeArray($newFields);
    }
}
