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
            'scholarships.academic_period_id' => $academicPeriodId
        ];

        if ($financialAssistanceType != -1) {
            $conditions['scholarships.scholarship_financial_assistance_type_id'] = $financialAssistanceType;
        }
//POCOR-7959 :: Start
        $join = [];

        $join['workflow_steps'] = [
            'type' => 'inner',
            'table' => "workflow_steps",
            'conditions' => [
                'workflow_steps.id = ScholarshipApplications.status_id'
            ]
        ];

        $join['security_users'] = [
            'type' => 'inner',
            'table' => "security_users",
            'conditions' => [
                'security_users.id = ScholarshipApplications.applicant_id'
            ]
        ];
        $join['scholarships'] = [
            'type' => 'inner',
            'table' => "scholarships",
            'conditions' => [
                'scholarships.id = ScholarshipApplications.scholarship_id'
            ]
        ];
        $join['academic_periods'] = [
            'type' => 'inner',
            'table' => "academic_periods",
            'conditions' => [
                'academic_periods.id = scholarships.academic_period_id'
            ]
        ];
        $join['scholarship_financial_assistance_types'] = [
            'type' => 'inner',
            'table' => "scholarship_financial_assistance_types",
            'conditions' => [
                'scholarship_financial_assistance_types.id = scholarships.scholarship_financial_assistance_type_id'
            ]
        ];
        $join['genders'] = [
            'type' => 'inner',
            'table' => "genders",
            'conditions' => [
                'genders.id = security_users.gender_id'
            ]
        ];
        $join['scholarship_application_institution_choices'] = [
            'type' => 'left',
            'table' => "scholarship_application_institution_choices",
            'conditions' => [
                'scholarship_application_institution_choices.applicant_id = ScholarshipApplications.applicant_id',
                'scholarship_application_institution_choices.scholarship_id = ScholarshipApplications.scholarship_id'
            ]
        ];
        $join['scholarship_institution_choice_statuses'] = [
            'type' => 'left',
            'table' => "scholarship_institution_choice_statuses",
            'conditions' => [
                'scholarship_institution_choice_statuses.id = scholarship_application_institution_choices.scholarship_institution_choice_status_id'
            ]
        ];
        $join['qualification_levels'] = [
            'type' => 'left',
            'table' => "qualification_levels",
            'conditions' => [
                'qualification_levels.id = scholarship_application_institution_choices.qualification_level_id'
            ]
        ];
        $join['scholarship_institution_choice_types'] = [
            'type' => 'left',
            'table' => "scholarship_institution_choice_types",
            'conditions' => [
                'scholarship_institution_choice_types.id = scholarship_application_institution_choices.scholarship_institution_choice_type_id'
            ]
        ];
        $join['countries'] = [
            'type' => 'left',
            'table' => "countries",
            'conditions' => [
                'countries.id = scholarship_application_institution_choices.country_id'
            ]
        ];

        $join['area_administratives'] = [
            'type' => 'left',
            'table' => "(SELECT security_users.id security_user_id
            ,area_administrative_levels.level
            ,CASE
                WHEN area_administrative_levels.level = 6 THEN first_layer.name
                ELSE ''
            END AS municipality
            ,CASE
                WHEN area_administrative_levels.level = 6 THEN second_layer.name
                WHEN area_administrative_levels.level = 5 THEN first_layer.name
                ELSE ''
            END AS qada
            ,CASE
                WHEN area_administrative_levels.level = 6 THEN third_layer.name
                WHEN area_administrative_levels.level = 5 THEN second_layer.name
                WHEN area_administrative_levels.level = 4 THEN first_layer.name
                ELSE ''
            END AS liwa
            ,CASE
                WHEN area_administrative_levels.level = 6 THEN fourth_layer.name
                WHEN area_administrative_levels.level = 5 THEN third_layer.name
                WHEN area_administrative_levels.level = 4 THEN second_layer.name
                WHEN area_administrative_levels.level = 3 THEN first_layer.name
                ELSE ''
            END AS governorate
            ,CASE
                WHEN area_administrative_levels.level = 6 THEN fifth_layer.name
                WHEN area_administrative_levels.level = 5 THEN fourth_layer.name
                WHEN area_administrative_levels.level = 4 THEN third_layer.name
                WHEN area_administrative_levels.level = 3 THEN second_layer.name
                WHEN area_administrative_levels.level = 2 THEN first_layer.name
                ELSE ''
            END AS region
            ,CASE
                WHEN area_administrative_levels.level = 6 THEN sixth_layer.name
                WHEN area_administrative_levels.level = 5 THEN fifth_layer.name
                WHEN area_administrative_levels.level = 4 THEN fourth_layer.name
                WHEN area_administrative_levels.level = 3 THEN third_layer.name
                WHEN area_administrative_levels.level = 2 THEN second_layer.name
                WHEN area_administrative_levels.level = 1 THEN first_layer.name
                ELSE ''
            END AS country
        FROM security_users
        LEFT JOIN area_administratives first_layer
        ON first_layer.id = security_users.address_area_id
        LEFT JOIN area_administratives second_layer
        ON second_layer.id = first_layer.parent_id
        LEFT JOIN area_administratives third_layer
        ON third_layer.id = second_layer.parent_id
        LEFT JOIN area_administratives fourth_layer
        ON fourth_layer.id = third_layer.parent_id
        LEFT JOIN area_administratives fifth_layer
        ON fifth_layer.id = fourth_layer.parent_id
        LEFT JOIN area_administratives sixth_layer
        ON sixth_layer.id = fifth_layer.parent_id
        LEFT JOIN area_administratives seventh_layer
        ON seventh_layer.id = sixth_layer.parent_id
        LEFT JOIN area_administrative_levels
        ON area_administrative_levels.id = first_layer.area_administrative_level_id)",
            'conditions' => [
                'area_administratives.security_user_id = security_users.id'
            ]
        ];

        $join['contact_info'] = [
            'type' => 'left',
            'table' => "(SELECT user_contacts.security_user_id
            ,GROUP_CONCAT(CONCAT(' ', contact_options.name, ' (', contact_types.name, '): ', user_contacts.value)) contacts
        FROM user_contacts
        INNER JOIN contact_types
        ON contact_types.id = user_contacts.contact_type_id
        INNER JOIN contact_options
        ON contact_options.id = contact_types.contact_option_id
        WHERE user_contacts.preferred = 1
        GROUP BY user_contacts.security_user_id)",
            'conditions' => [
                'contact_info.security_user_id = security_users.id'
            ]
        ];
        $join['student_nationalities'] = [
            'type' => 'left',
            'table' => "(SELECT  user_nationalities.security_user_id
            ,nationalities.name nationality_name
        FROM user_nationalities
        INNER JOIN nationalities
        ON nationalities.id = user_nationalities.nationality_id
        WHERE user_nationalities.preferred = 1
        GROUP BY  user_nationalities.security_user_id)",
            'conditions' => [
                'student_nationalities.security_user_id = security_users.id'
            ]
        ];


        $query->select([
                        'academic_period' => 'academic_periods.name',
                        'status' => 'workflow_steps.name',
                        'openemis_no' => 'security_users.openemis_no',
                        'first_name' => 'security_users.first_name',
                        'middle_name' => "(IFNULL(security_users.middle_name, ''))",
                        'third_name' => "(IFNULL(security_users.third_name, ''))",
                        'last_name' => 'security_users.last_name',
                        'genders' => 'genders.name',
                        'preferred_nationality' => "(IFNULL(student_nationalities.nationality_name, ''))",

                        'contacts' => "(IFNULL(contact_info.contacts, ''))",
                        'email' => "(IFNULL(security_users.email, ''))",
                        'address_area' => "(IF(LENGTH(area_administratives.municipality) > 0, area_administratives.municipality,
                        IF(LENGTH(area_administratives.qada) > 0, area_administratives.qada, 
                            IF(LENGTH(area_administratives.liwa) > 0, area_administratives.liwa, 
                                IF(LENGTH(area_administratives.governorate) > 0, area_administratives.governorate, 
                                    IF(LENGTH(area_administratives.region) > 0, area_administratives.region, ''))))))",
                        'course_name' => "(IFNULL(scholarship_application_institution_choices.course_name, ''))",
                        'course_level' => "(IFNULL(qualification_levels.name, ''))",
                        'institution' => "(IFNULL(scholarship_institution_choice_types.name, ''))",
                        'institution_location_region' => "(IFNULL(scholarship_application_institution_choices.location_type, ''))",
                        'institution_location_country' => "(IFNULL(countries.name, ''))",

                        'institution_status' => "(IFNULL(scholarship_institution_choice_statuses.name, ''))",
                        'award_category' => "(IFNULL(scholarship_financial_assistance_types.name, ''))",
                        'total_award_amount' => "(IFNULL(scholarships.total_amount, ''))"

        ])
        ->join($join)
        ->where([$conditions]);
        //POCOR-7959 :: End
    }
    
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {       
        $newArray = [];
        $newArray[] = [
            'key' => 'academic_period',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];
        $newArray[] = [
            'key' => 'status',
            'field' => 'status',
            'type' => 'string',
            'label' => __('Status')
        ];
        $newArray[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' =>  __('OpenEMIS ID')
        ];
        $newArray[] = [
            'key' => 'first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' =>  __('First Name')
        ];

        $newArray[] = [
            'key' => 'middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' =>  __('Middle Name')
        ];

        $newArray[] = [
            'key' => 'third_name',
            'field' => 'third_name',
            'type' => 'string',
            'label' =>  __('Third Name')
        ];

        $newArray[] = [
            'key' => 'last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' =>  __('Last Name')
        ];

        $newArray[] = [
            'key' => 'genders',
            'field' => 'genders',
            'type' => 'string',
            'label' =>  __('Gender')
        ];

        $newArray[] = [
            'key' => 'preferred_nationality',
            'field' => 'preferred_nationality',
            'type' => 'string',
            'label' =>  __('Preferred Nationality')
        ];
        $newArray[] = [
            'key' => 'contacts',
            'field' => 'contacts',
            'type' => 'string',
            'label' => __('Contacts')
        ];
        $newArray[] = [
            'key' => 'email',
            'field' => 'email',
            'type' => 'string',
            'label' => __('Email')
        ];
        $newArray[] = [
            'key' => 'address_area',
            'field' => 'address_area',
            'type' => 'string',
            'label' => __('Address Area')
        ];
        $newArray[] = [
            'key' => 'course_name',
            'field' => 'course_name',
            'type' => 'string',
            'label' => __('Course Name')
        ];
        $newArray[] = [
            'key' => 'course_level',
            'field' => 'course_level',
            'type' => 'string',
            'label' => __('Course Level')
        ];
        $newArray[] = [
            'key' => 'institution',
            'field' => 'institution',
            'type' => 'string',
            'label' => __('Institution')
        ];
        $newArray[] = [
            'key' => 'institution_location_region',
            'field' => 'institution_location_region',
            'type' => 'string',
            'label' => __('Institution location-Region')
        ];
        $newArray[] = [
            'key' => 'institution_location_country',
            'field' => 'institution_location_country',
            'type' => 'string',
            'label' => __('Institution location-Country')
        ];
        $newArray[] = [
            'key' => 'institution_status',
            'field' => 'institution_status',
            'type' => 'string',
            'label' => __('Institution Status')
        ];
        $newArray[] = [
            'key' => 'award_category',
            'field' => 'award_category',
            'type' => 'string',
            'label' => __('Award Category')
        ];
        $newArray[] = [
            'key' => 'total_award_amount',
            'field' => 'total_award_amount',
            'type' => 'string',
            'label' => __('Total Award Amount')
        ];

        $fields->exchangeArray($newArray);
    }
}
