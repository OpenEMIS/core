<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffQualificationsTable extends AppTable  {
    public function initialize(array $config) {
        $this->table('staff_qualifications');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('QualificationTitles',     ['className' => 'FieldOption.QualificationTitles']);
        $this->belongsTo('QualificationCountries',  ['className' => 'FieldOption.Countries', 'foreignKey' => 'qualification_country_id']);
        $this->belongsTo('FieldOfStudies', ['className' => 'Education.EducationFieldOfStudies', 'foreignKey' => 'education_field_of_study_id']);

        $this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'staff_qualifications_subjects',
            'foreignKey' => 'staff_qualification_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Staff.QualificationsSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('QualificationSpecialisations', [
            'className' => 'FieldOption.QualificationSpecialisations',
            'joinTable' => 'staff_qualifications_specialisations',
            'foreignKey' => 'staff_qualification_id',
            'targetForeignKey' => 'qualification_specialisation_id',
            'through' => 'Staff.QualificationsSpecialisations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Excel', [
            'excludes' => [
                'file_name'
            ],
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event) {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {

        $requestData = json_decode($settings['process']['params']);

        $userId = $requestData->user_id;
        $superAdmin = $requestData->super_admin;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('staff_id'),
                $this->aliasField('qualification_title_id'),
                $this->aliasField('education_field_of_study_id'),
                $this->aliasField('qualification_country_id'),
                $this->aliasField('qualification_institution'),
                $this->aliasField('document_no'),
                $this->aliasField('graduate_year'),
                $this->aliasField('gpa'),
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'staff_position_name' => 'StaffPositionTitles.name',
                'staff_type_name' => 'StaffTypes.name',
                'qualification_level' => 'QualificationLevels.name',
                'identity_type_id' => 'Users.identity_type_id',
                'identity_number' => 'Users.identity_number'
            ])
            ->contain([
                'QualificationTitles.QualificationLevels',
                'EducationSubjects' => [
                    'fields' => [
                        'EducationSubjects.id',
                        'QualificationsSubjects.staff_qualification_id',
                        'EducationSubjects.name',
                        'EducationSubjects.code'
                    ]
                ],
                'QualificationSpecialisations' => [
                    'fields' => [
                        'QualificationSpecialisations.id',
                        'QualificationsSpecialisations.staff_qualification_id',
                        'QualificationSpecialisations.name'
                    ]
                ],
                'FieldOfStudies' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'QualificationTitles' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'QualificationCountries' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Users' => [
                    'fields' => [
                        'first_name',
                        'last_name',
                        'identity_type_id',
                        'identity_number'
                    ]
                ]

            ])
            ->innerJoin(
                ['InstitutionStaff' => 'institution_staff'],
                    ['InstitutionStaff.staff_id = '.$this->aliasField('staff_id')]
            )
            ->innerJoin(
                ['Institutions' => 'institutions'],
                    ['Institutions.id = InstitutionStaff.institution_id']
            )
            ->innerJoin(
                ['InstitutionPositions' => 'institution_positions'],
                    ['InstitutionPositions.id = InstitutionStaff.institution_position_id']
            )
            ->innerJoin(
                ['StaffPositionTitles' => 'staff_position_titles'],
                    ['StaffPositionTitles.id = InstitutionPositions.staff_position_title_id']
            )
            ->innerJoin(
                ['StaffTypes' => 'staff_types'],
                    ['InstitutionStaff.staff_type_id = StaffTypes.id']
            );
      
        if (!$superAdmin) {
            $query->find('ByAccess', ['user_id' => $userId, 'institution_field_alias' => 'Institutions.id']);
        }
    }

    public function onExcelGetSpecialisations(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('qualification_specialisations')) {
            if (!empty($entity->qualification_specialisations)) {
                $specialisations = $entity->qualification_specialisations;
                foreach ($specialisations as $key => $value) {
                    $return[] = $value->name;
                }
            }
        }
        return implode(', ', array_values($return));
    }

    public function onExcelGetSubjects(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('education_subjects')) {
            if (!empty($entity->education_subjects)) {
                $subjects = $entity->education_subjects;
                foreach ($subjects as $key => $value) {
                    $return[] = '(' . $value->code . ')' . ' - ' . $value->name;
                }
            }
        }
        return implode(', ', array_values($return));
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'StaffQualifications.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.name',
            'field' => 'staff_position_name',
            'type' => 'string',
            'label' => __('Position')
        ];
        $newFields[] = [
            'key' => 'StaffTypes.name',
            'field' => 'staff_type_name',
            'type' => 'string',
            'label' => __('Staff Type')
        ];

        $newFields[] = [
            'key' => 'StaffQualifications.qualification_title_id',
            'field' => 'qualification_title_id',
            'type' => 'integer',
            'label' => __('Title')
        ];

        $newFields[] = [
            'key' => 'QualificationLevels.name',
            'field' => 'qualification_level',
            'type' => 'string',
            'label' => __('Level')
        ];

        $newFields[] = [
            'key' => 'StaffQualifications.education_field_of_study_id',
            'field' => 'education_field_of_study_id',
            'type' => 'integer',
            'label' => __('Field Of Study')
        ];

        $newFields[] = [
            'key' => 'StaffQualifications.specialisations',
            'field' => 'specialisations',
            'type' => 'string',
            'label' => __('Specialisations')
        ];

        $newFields[] = [
            'key' => 'StaffQualifications.subjects',
            'field' => 'subjects',
            'type' => 'string',
            'label' => __('Subjects')
        ];

        $newFields[] = [
            'key' => 'StaffQualifications.qualification_country_id',
            'field' => 'qualification_country_id',
            'type' => 'integer',
            'label' => __('Country')
        ];

        $newFields[] = [
            'key' => 'StaffQualifications.qualification_institution',
            'field' => 'qualification_institution',
            'type' => 'string',
            'label' => __('Institution')
        ];

        $newFields[] = [
            'key' => 'StaffQualifications.document_no',
            'field' => 'document_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffQualifications.graduate_year',
            'field' => 'graduate_year',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffQualifications.gpa',
            'field' => 'gpa',
            'type' => 'string',
            'label' => ''
        ];
        
        $newFields[] = [
            'key' => 'Users.identity_type_id',
            'field' => 'identity_type_id',
            'type' => 'string',
            'label' => ''
        ];
        
        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => ''
        ];
        
        $fields->exchangeArray($newFields);
    }
}