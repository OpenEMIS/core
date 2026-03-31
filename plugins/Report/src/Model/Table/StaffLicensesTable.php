<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use Cake\I18n\FrozenDate;

class StaffLicensesTable extends AppTable  {
    public function initialize(array $config): void
    {
        $this->setTable('staff_licenses');
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

    //POCOR-9418 query changes
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;

        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');

        $conditions = [];

        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['InstitutionStaff.institution_id'] = $institutionId;
        }

        if (!empty($areaId) && $areaId != -1) {
            $conditions[$InstitutionsTable->aliasField('area_id')] = $areaId;
        }

        // Build query
        $query
            ->select([
                'openemis_no' => 'Users.openemis_no',
                'assignee_name' => $query->func()->concat([
                    'Assignees.first_name' => 'literal',
                    " ",
                    'Assignees.last_name' => 'literal'
                ]),
                'security_user_name' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                ]),
                'issue_date' => $this->aliasField('issue_date'),
                'expiry_date' => $this->aliasField('expiry_date'),
                'license_number' => $this->aliasField('license_number'),
            ])
            ->contain(['Users', 'Classifications', 'Assignees'])
            ->leftJoin(
                ['InstitutionStaff' => 'institution_staff'],
                ['InstitutionStaff.staff_id = ' . $this->aliasField('security_user_id')]
            )
            ->leftJoin(
                [$InstitutionsTable->getAlias() => $InstitutionsTable->getTable()],
                [$InstitutionsTable->aliasField('id = ') . 'InstitutionStaff.institution_id']
            )
            ->where($conditions)
            ->distinct(['StaffLicenses.id'])
            ->order([$this->aliasField('security_user_id')]);


            if (!empty($selectedStatus) && $selectedStatus != '-1') {
                $query->where([$this->aliasField('status_id') => $selectedStatus]);
            }
           $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    // If there is no license, clear the license-related fields
                    if (empty($row->license_number)) {
                        $row->assignee_name = null;
                        $row->issue_date = null;
                        $row->expiry_date = null;
                        $row->license_number = null;
                        $row->license_type_id = null;
                        $row->status_id = null;
                        $row->comments = null;
                        $row->classifications = null;
                        $row->issuer = null;
                        return $row;
                    }else{
                        return $row;
                    }

                });
            });
    }


    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newArray = [];

        $newArray[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => '',
        ];

        $newArray[] = [
            'key' => 'security_user_id',
            'field' => 'security_user_id',
            'type' => 'string',
            'label' => __('Name'),
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
            'label' => __('License Type'),
        ];

        $newArray[] = [
            'key' => 'assignee_name',
            'field' => 'assignee_name',
            'type' => 'string',
            'label' => __('Assignee'),
        ];

        $newArray[] = [
            'key' => 'StaffLicenses.license_number',
            'field' => 'license_number',
            'type' => 'string',
            'label' => __('License Number'),
        ];

        $newArray[] = [
            'key' => 'issue_date',
            'field' => 'issue_date',
            'type' => 'string',
            'label' => __('Issue Date'),
        ];

        $newArray[] = [
            'key' => 'expiry_date',
            'field' => 'expiry_date',
            'type' => 'string',
            'label' => __('Expiry Date'),
        ];

        $newArray[] = [
            'key' => 'issuer',
            'field' => 'issuer',
            'type' => 'string',
            'label' => __('Issuer'),
        ];

        $newArray[] = [
            'key' => 'StaffLicenses.comments',
            'field' => 'comments',
            'type' => 'string',
            'label' => __('Comments'),
        ];

        $newArray[] = [
            'key' => 'StaffLicenses.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => __('Status'),
        ];

        // Replace existing fields instead of merging
        $fields->exchangeArray($newArray);
    }

    public function onExcelGetOpenemisNo(EventInterface $event, Entity $entity)
    {
        $security_user_id = $entity['security_user_id'];
        $user = self::getRelatedRecord('security_users', $security_user_id);
        $entity['security_user'] = $user['first_name'] . ' ' . $user['last_name'];
        return $user['openemis_no'];
    }

    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
<<<<<<< HEAD
     *
=======

>>>>>>> 30c1e730a8ff7bbb59a0ed44166ad027a97a39da
     */
    private static function getRelatedRecord($tableName, $relatedField)
    {
        if (!$relatedField) {
            return null;
        }
        $Table = TableRegistry::getTableLocator()->get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            return null;
        }
        return null;
    }


    public function onExcelGetClassification(EventInterface $event, Entity $entity)
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

    //POCOR-9418
    public function onExcelGetIssueDate(EventInterface $event, Entity $entity)
    {
        if(!empty($entity->issue_date)){
            return isset($entity->issue_date) ? $entity->issue_date->format('Y-m-d') : '';
        }else{
            return '';
        }
    }

    //POCOR-9418
    public function onExcelGetExpiryDate(EventInterface $event, Entity $entity)
    {
        if(!empty($entity->expiry_date)){
            return isset($entity->expiry_date) ? $entity->expiry_date->format('Y-m-d') : '';
        }else{
            return '';
        }
    }

    /**
     * @param $query
     */
   /* private function addInstitutionJoinToQuery($query)
    {
        $InstitutionStaffTable = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $query
            ->leftJoin([$InstitutionStaffTable->getAlias() => $InstitutionStaffTable->getTable()], [
                $InstitutionStaffTable->aliasField('staff_id') => $this->aliasField('security_user_id')
            ])->leftJoin([$InstitutionsTable->getAlias() => $InstitutionsTable->getTable()], [
                $InstitutionsTable->aliasField('id') => $InstitutionStaffTable->aliasField('institution_id')
            ]);
    }*/

    public function onExcelBeforeQuerybkp(EventInterface $event, ArrayObject $settings, $query)
    {
//        $query = $this->addInstitutionJoinToQuery($query);
        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $InstitutionStaffTable = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
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
            ->leftJoin([$InstitutionsTable->getAlias() => $InstitutionsTable->getTable()], [
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

    //    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
//    {
//        $requestData = json_decode($settings['process']['params']);
//        $selectedStatus = $requestData->status;
//        $areaId = $requestData->area_education_id;
//        $institutionId = $requestData->institution_id;
//        $academicPeriodId = $requestData->academic_period_id;
//        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
//        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
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


}
