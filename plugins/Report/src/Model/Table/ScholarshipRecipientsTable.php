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

class ScholarshipRecipientsTable extends AppTable  {

    use OptionsTrait;

    private $interestRateOptions = [];

    public function initialize(array $config) 
    {
        
        $this->table('scholarship_recipients');
        parent::initialize($config);

        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('RecipientActivityStatuses', ['className' => 'Scholarship.RecipientActivityStatuses', 'foreignKey' => 'scholarship_recipient_activity_status_id']);
        
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

    public function onExcelGetAcademic(Event $event, Entity $entity)
    {
        $academic = '';
        if (!is_null($entity->scholarship->academic_period->name)) {
            $academic = $entity->scholarship->academic_period->name;
        }

        return $academic;
    }

    public function onExcelGetLocation(Event $event, Entity $entity)
    {
        $location = '';
        if (!is_null($entity->ApplicationInstitutionChoices['location_type']) && $entity->ApplicationInstitutionChoices['is_selected'] == 1) {
            $location = $entity->ApplicationInstitutionChoices['location_type'];
        }

        return $location;
    }

    public function onExcelGetCountry(Event $event, Entity $entity)
    {
        $country = '';
        if (!is_null($entity->Countries['name']) && $entity->ApplicationInstitutionChoices['is_selected'] == 1) {
            $country = $entity->Countries['name'];
        }

        return $country;
    }

    public function onExcelGetApprovedAmount(Event $event, Entity $entity)
    {
        $amount = '';
        if (!is_null($entity->approved_amount) && $entity->ApplicationInstitutionChoices['is_selected'] == 1) {
            $amount = $entity->approved_amount;
        }

        return $amount;
    }

    public function onExcelGetFieldOfStudy(Event $event, Entity $entity)
    {
        $fieldOfStudy = '';
        if (!is_null($entity->EducationFieldOfStudies['name']) && $entity->ApplicationInstitutionChoices['is_selected'] == 1) {
            $fieldOfStudy = $entity->EducationFieldOfStudies['name'];
        }

        return $fieldOfStudy;
    } 

    public function onExcelGetCourse(Event $event, Entity $entity)
    {
        $course = '';
        if (!is_null($entity->ApplicationInstitutionChoices['course_name']) && $entity->ApplicationInstitutionChoices['is_selected'] == 1) {
            $course = $entity->ApplicationInstitutionChoices['course_name'];
        }

        return $course;
    }

    public function onExcelGetQualificationLevel(Event $event, Entity $entity)
    {
        $qualificationLevel = '';
        if (!is_null($entity->QualificationLevels['name']) && $entity->ApplicationInstitutionChoices['is_selected'] == 1) {
            $qualificationLevel = $entity->QualificationLevels['name'];
        }

        return $qualificationLevel;
    }

    public function onExcelGetStartDate(Event $event, Entity $entity)
    {
        $startDate = '';
        if (!is_null($entity->ApplicationInstitutionChoices['start_date']) && $entity->ApplicationInstitutionChoices['is_selected'] == 1) {
            $startDate = $entity->ApplicationInstitutionChoices['start_date'];
        }

        return $startDate;
    }

    public function onExcelGetEndDate(Event $event, Entity $entity)
    {
        $endDate = '';
        if (!is_null($entity->ApplicationInstitutionChoices['end_date']) && $entity->ApplicationInstitutionChoices['is_selected'] == 1) {
            $endDate = $entity->ApplicationInstitutionChoices['end_date'];
        }

        return $endDate;
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

        $ApplicationInstitutionChoices = TableRegistry::get('Scholarship.ApplicationInstitutionChoices');
        $InstitutionChoiceTypes = TableRegistry::get('Scholarship.InstitutionChoiceTypes');
        $Country = TableRegistry::get('FieldOption.Countries');
        $EducationFieldOfStudies = TableRegistry::get('Education.EducationFieldOfStudies');
        $QualificationLevels = TableRegistry::get('FieldOption.QualificationLevels');

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
                'RecipientActivityStatuses' => [
                    'fields' => [
                        'id',
                        'name'
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'name',
                        'academic_period_id'
                    ]
                ],
                'Scholarships.AcademicPeriods' => [
                    'fields' => [
                        'name',
                    ]
                ],
            ])
            ->leftJoin([$ApplicationInstitutionChoices->alias() => $ApplicationInstitutionChoices->table()], [
                $ApplicationInstitutionChoices->aliasField('is_selected = 1'),$this->aliasField('recipient_id =') . $ApplicationInstitutionChoices->aliasField('applicant_id')
            ])
            ->leftJoin([$InstitutionChoiceTypes->alias() => $InstitutionChoiceTypes->table()], [
                $InstitutionChoiceTypes->aliasField('id =') . $ApplicationInstitutionChoices->aliasField('scholarship_institution_choice_type_id'),
            ])
            ->leftJoin([$Country->alias() => $Country->table()], [
                $Country->aliasField('id =') . $ApplicationInstitutionChoices->aliasField('country_id'),
            ])
            ->leftJoin([$EducationFieldOfStudies->alias() => $EducationFieldOfStudies->table()], [
                $EducationFieldOfStudies->aliasField('id =') . $ApplicationInstitutionChoices->aliasField('education_field_of_study_id'),
            ])
            ->leftJoin([$QualificationLevels->alias() => $QualificationLevels->table()], [
                $QualificationLevels->aliasField('id =') . $ApplicationInstitutionChoices->aliasField('qualification_level_id'),
            ])
            ->select([
                $this->aliasField('recipient_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('approved_amount'),
                $this->aliasField('scholarship_recipient_activity_status_id'),
                $ApplicationInstitutionChoices->aliasField('location_type'),
                $ApplicationInstitutionChoices->aliasField('country_id'),
                $ApplicationInstitutionChoices->aliasField('education_field_of_study_id'),
                $ApplicationInstitutionChoices->aliasField('course_name'), 
                $ApplicationInstitutionChoices->aliasField('qualification_level_id'),
                $ApplicationInstitutionChoices->aliasField('start_date'),
                $ApplicationInstitutionChoices->aliasField('end_date'), 
                $ApplicationInstitutionChoices->aliasField('is_selected'),
                $Country->aliasField('name'),
                $EducationFieldOfStudies->aliasField('name'),
                $QualificationLevels->aliasField('name'),
                'institution_name' => 'InstitutionChoiceTypes.name'
            ])
            ->where($conditions);
    }
    
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {       
       $newFields = [];

        $newFields[] = [
            'key' => 'Scholarships.AcademicPeriods',
            'field' => 'academic',
            'type' => 'string',
            'label' => __('Academic Period')
        ];  

        $newFields[] = [
            'key' => 'Recipients.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $newFields[] = [
            'key' => 'Recipients.recipient_id',
            'field' => 'recipient_id',
            'type' => 'integer',
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
            'key' => 'Recipients.scholarship_recipient_activity_status_id',
            'field' => 'scholarship_recipient_activity_status_id',
            'type' => 'string',
            'label' => __('Status')
        ];

        $newFields[] = [
            'key' => 'ScholarshipApplications.scholarship_id',
            'field' => 'scholarship_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'ApplicationInstitutionChoices.approved_amount',
            'field' => 'approvedAmount',
            'type' => 'string',
            'label' => __('Approved Award Amount')
        ];        

        $newFields[] = [
            'key' => 'ApplicationInstitutionChoices.location_type',
            'field' => 'location',
            'type' => 'string',
            'label' => __('Location Type')
        ];

        $newFields[] = [
            'key' => 'ScholarshipApplications.country_id',
            'field' => 'country',
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
            'key' => 'ScholarshipApplications.education_field_of_study_id',
            'field' => 'fieldOfStudy',
            'type' => 'string',
            'label' => __('Area of Study')
        ];

        $newFields[] = [
            'key' => 'ScholarshipApplications.course',
            'field' => 'course',
            'type' => 'string',
            'label' => __('Course Name')
        ];

        $newFields[] = [
            'key' => 'ScholarshipApplications.qualification_level_id',
            'field' => 'qualificationLevel',
            'type' => 'string',
            'label' => __('Qualification Level')
        ];

        $newFields[] = [
            'key' => 'ApplicationInstitutionChoices.start_date',
            'field' => 'startDate',
            'type' => 'string',
            'label' => __('Start Date')
        ];

        $newFields[] = [
            'key' => 'ScholarshipApplications.end_date',
            'field' => 'endDate',
            'type' => 'string',
            'label' => __('End Date')
        ];

        $fields->exchangeArray($newFields);
    }
}
