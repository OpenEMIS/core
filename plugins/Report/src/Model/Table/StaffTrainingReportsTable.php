<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class StaffTrainingReportsTable extends AppTable {

    public function initialize(array $config) {
        $this->table('staff_trainings');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffTrainingCategories', ['className' => 'Staff.StaffTrainingCategories', 'foreignKey' => 'staff_training_category_id']);
        $this->belongsTo('TrainingFieldStudies', ['className' => 'Training.TrainingFieldStudies', 'foreignKey' => 'training_field_of_study_id']);

        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => false
        ]);
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
    
    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $identityTypeName = '';
        if (!empty($entity->identity_type)) {
            $identityType = TableRegistry::get('FieldOption.IdentityTypes')->find()->where(['id'=>$entity->identity_type])->first();
            $identityTypeName = $identityType->name;
        }
        return $identityTypeName;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $conditions = [];
        if (!empty($academicPeriodId)) {
                $conditions['OR'] = [
                    'OR' => [
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' <=' => $startDate,
                            'InstitutionStaff.end_date' . ' >=' => $startDate
                        ],
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' <=' => $endDate,
                            'InstitutionStaff.end_date' . ' >=' => $endDate
                        ],
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' >=' => $startDate,
                            'InstitutionStaff.end_date' . ' <=' => $endDate
                        ]
                    ],
                    [
                        'InstitutionStaff.end_date' . ' IS NULL',
                        'InstitutionStaff.start_date' . ' <=' => $endDate
                    ]
                ];
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['InstitutionStaff.institution_id'] = $institutionId; 
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions[$InstitutionsTable->aliasField('area_id')] = $areaId; 
        }
        $query->contain([
            'Users' => [
                'fields' => [
                    'openemis_no' => 'Users.openemis_no',
                    'Users.first_name',
                    'Users.middle_name',
                    'Users.third_name',
                    'Users.last_name',
                    'date_of_birth' => 'Users.date_of_birth',
                    'identity_number' => 'Users.identity_number',
                    'identity_type' => 'Users.identity_type_id'
                ]
            ]
        ])
        ->leftJoin(['InstitutionStaff' => 'institution_staff'], [
            'InstitutionStaff.staff_id = ' . $this->aliasField('staff_id')
        ])
        ->leftJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()], [
            $InstitutionsTable->aliasField('id = ') . 'InstitutionStaff.institution_id'
        ]);
        $query->where(['Users.is_staff' => 1, $conditions]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
        $extraFields = [];
        
        $extraFields[] = [
            'key' => 'code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Course Code')
        ];
        
        $extraFields[] = [
            'key' => 'name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Course Name')
        ];
        
        $extraFields[] = [
            'key' => 'description',
            'field' => 'description',
            'type' => 'string',
            'label' => __('Course Description')
        ];
        
        $extraFields[] = [
            'key' => 'staff_training_category_id',
            'field' => 'staff_training_category_id',
            'type' => 'string',
            'label' => __('Staff Training Category')
        ];
        
        $extraFields[] = [
            'key' => 'training_field_of_study_id',
            'field' => 'training_field_of_study_id',
            'type' => 'string',
            'label' => __('Field of Study')
        ];
        
        $extraFields[] = [
            'key' => 'credit_hours',
            'field' => 'credit_hours',
            'type' => 'string',
            'label' => __('Credit Hours')
        ];
        
        $extraFields[] = [
            'key' => 'completed_date',
            'field' => 'completed_date',
            'type' => 'date',
            'label' => __('Completed Date')
        ];
        $extraFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        
        $extraFields[] = [
            'key' => 'staff_id',
            'field' => 'staff_id',
            'type' => 'string',
            'label' => __('Staff Name')
        ];

        $extraFields[] = [
            'key' => 'Users.identity_type_id',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $extraFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        
        $fields->exchangeArray($extraFields);
    }

}
