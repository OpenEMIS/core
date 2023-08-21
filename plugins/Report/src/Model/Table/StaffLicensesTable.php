<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffLicensesTable extends AppTable  {
    public function initialize(array $config)
    {
        $this->table('staff_licenses');
        parent::initialize($config);
        $this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('LicenseTypes', ['className' => 'FieldOption.LicenseTypes']);
        $this->belongsTo('Assignees', ['className' => 'User.Users',  'foreignKey' => 'assignee_id']);
        $this->belongsToMany('Classifications', [
            'className' => 'FieldOption.LicenseClassifications',
            'joinTable' => 'staff_licenses_classifications',
            'foreignKey' => 'staff_license_id',
            'targetForeignKey' => 'license_classification_id',
            'through' => 'Staff.StaffLicensesClassifications',
            'dependent' => true
        ]);

        $this->addBehavior('Excel', [
            'excludes' => ['status_id', 'assignee_id', 'staff_id', 'license_type_id'],
            'pages' => false,
            'orientation' => 'landscape'
        ]);
        $this->addBehavior('Report.ReportList');
    }
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
//        $query = $this->addInstitutionJoinToQuery($query);
        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $InstitutionStaffTable = TableRegistry::get('Institution.InstitutionStaff');
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
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
        $query
            ->select(['openemis_no' => 'Users.openemis_no'])
            ->contain(['Users', 'Classifications'])
            ->leftJoin(['InstitutionStaff' => 'institution_staff'], [
                'InstitutionStaff.staff_id = ' . $this->aliasField('security_user_id')
            ])
            ->leftJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()], [
                $InstitutionsTable->aliasField('id = ') . 'InstitutionStaff.institution_id'
            ])
            ->where([$conditions])
            ->order([$this->aliasField('security_user_id')]);
        if ($selectedStatus != '-1') {
            $query
                ->matching('WorkflowSteps.WorkflowStatuses', function ($q) use ($selectedStatus, $conditions) {
                    return $q->where(['WorkflowStatuses.id' => $selectedStatus, $conditions]);
                });
        }

    }

    public function onExcelGetOpenemisNo(Event $event, Entity $entity)
    {
//        $this->log('onExcelGetOpenemisNo', 'debug');
//        $this->log($entity, 'debug');
//        $this->log($this->query()->sql(), 'debug');
        $security_user_id = $entity['security_user_id'];
        $user = self::getRelatedRecord('security_users', $security_user_id);
        $entity['security_user'] = $user['first_name'] . ' ' . $user['last_name'];
        return $user['openemis_no'];
    }

    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getRelatedRecord($tableName, $relatedField)
    {
        if (!$relatedField) {
            return null;
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            return null;
        }
        return null;
    }

//    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
//    {
//        $requestData = json_decode($settings['process']['params']);
//        $selectedStatus = $requestData->status;
//        $areaId = $requestData->area_education_id;
//        $institutionId = $requestData->institution_id;
//        $academicPeriodId = $requestData->academic_period_id;
//        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
//        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
//        $periodEntity = $AcademicPeriods->get($academicPeriodId);
//        $startDate = $periodEntity->start_date->format('Y-m-d');
//        $endDate = $periodEntity->end_date->format('Y-m-d');
////        $query = $this->addInstitutionJoinToQuery($query);
//        $conditions = [];
//        if (!empty($academicPeriodId)) {
//                $conditions['OR'] = [
//                    'OR' => [
//                        [
//                            'InstitutionStaff.end_date' . ' IS NOT NULL',
//                            'InstitutionStaff.start_date' . ' <=' => $startDate,
//                            'InstitutionStaff.end_date' . ' >=' => $startDate
//                        ],
//                        [
//                            'InstitutionStaff.end_date' . ' IS NOT NULL',
//                            'InstitutionStaff.start_date' . ' <=' => $endDate,
//                            'InstitutionStaff.end_date' . ' >=' => $endDate
//                        ],
//                        [
//                            'InstitutionStaff.end_date' . ' IS NOT NULL',
//                            'InstitutionStaff.start_date' . ' >=' => $startDate,
//                            'InstitutionStaff.end_date' . ' <=' => $endDate
//                        ]
//                    ],
//                    [
//                        'InstitutionStaff.end_date' . ' IS NULL',
//                        'InstitutionStaff.start_date' . ' <=' => $endDate
//                    ]
//                ];
//        }
//        if (!empty($institutionId) && $institutionId > 0) {
//            $conditions['InstitutionStaff.institution_id'] = $institutionId;
//        }
//        if (!empty($areaId) && $areaId != -1) {
//            $conditions[$InstitutionsTable->aliasField('area_id')] = $areaId;
//        }
//        $query
//            ->select(['openemis_no' => 'Users.openemis_no'])
//            ->contain(['Users', 'Classifications'])
//            ->leftJoin(['InstitutionStaff' => 'institution_staff'], [
//                'InstitutionStaff.staff_id = ' . $this->aliasField('staff_id')
//            ])
//            ->leftJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()], [
//                $InstitutionsTable->aliasField('id = ') . 'InstitutionStaff.institution_id'
//            ])
//            ->where([$conditions])
//            ->order([$this->aliasField('staff_id')]);
//
//        if ($selectedStatus != '-1') {
//            $query
//                ->matching('WorkflowSteps.WorkflowStatuses', function ($q) use ($selectedStatus, $conditions) {
//                    return $q->where(['WorkflowStatuses.id' => $selectedStatus, $conditions]);
//                });
//        }
//        $this->log($query->sql());
//    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newArray = [];

        $newArray[] = [
            'key' => 'StaffLicenses.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newArray[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => '',
        ];

        $newArray[] = [
            'key' => 'StaffLicenses.security_user_id',
            'field' => 'security_user_id',
            'type' => 'integer',
            'label' => 'Licencee',
        ];

        $newArray[] = [
            'key' => 'Classifications.name',
            'field' => 'classification',
            'type' => 'string',
            'label' => __('Classification'),
        ];

        $newArray[] = [
            'key' => 'StaffLicenses.license_type_id',
            'field' => 'license_type_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields = array_merge($newArray, $fields->getArrayCopy());
        $fields->exchangeArray($newFields);
    }

    public function onExcelGetClassification(Event $event, Entity $entity)
    {
        if ($entity->has('classifications')) {
            $classifications = [];
            foreach ($entity->classifications as $obj) {
                $classifications[] = $obj->name;
            }
            return implode(', ', $classifications);
        } else {
            return '';
        }
    }

    /**
     * @param $query
     */
    private function addInstitutionJoinToQuery($query)
    {
        $InstitutionStaffTable = TableRegistry::get('Institution.InstitutionStaff');
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $query
            ->leftJoin([$InstitutionStaffTable->alias() => $InstitutionStaffTable->table()], [
                $InstitutionStaffTable->aliasField('staff_id') => $this->aliasField('security_user_id')
            ])->leftJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()], [
                $InstitutionsTable->aliasField('id') => $InstitutionStaffTable->aliasField('institution_id')
            ]);
    }

}
