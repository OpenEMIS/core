<?php
/**
 * generate a report for staff  Staff with Missing Qualification
 * @author shikha sahu <shikha@metadesignsolutions.com>
 * POCOR-9262
 */
namespace Report\Model\Table;

use ArrayObject;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffWithMissingQualificationReportTable extends AppTable  {
    public function initialize(array $config): void {
        $this->setTable('institution_staff');
        parent::initialize($config);

        $this->addBehavior('Excel', [
            'excludes' => [
                'file_name'
            ],
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.AreaList');
    }

    public function beforeAction(EventInterface $event) {
        $controllerName = $this->controller->name;
        $reportName = __('Staff with Missing Qualification Report');
        $this->controller->Navigation->substituteCrumb($this->alias(), $reportName);
        $this->controller->set('contentHeader', __($controllerName).' - '.$reportName);
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, Request $request) {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }


    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $userId = $requestData->user_id;
        $superAdmin = $requestData->super_admin;
        $areaLevelId = $requestData->area_level_id;
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;

        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');

        $conditions = [];
        

        // Academic period filter
        if (!empty($academicPeriodId)) {
            $conditions[] = [
                'OR' => [
                    [
                        'InstitutionStaff.end_date IS NOT NULL',
                        'InstitutionStaff.start_date <=' => $startDate,
                        'InstitutionStaff.end_date >=' => $startDate
                    ],
                    [
                        'InstitutionStaff.end_date IS NOT NULL',
                        'InstitutionStaff.start_date <=' => $endDate,
                        'InstitutionStaff.end_date >=' => $endDate
                    ],
                    [
                        'InstitutionStaff.end_date IS NOT NULL',
                        'InstitutionStaff.start_date >=' => $startDate,
                        'InstitutionStaff.end_date <=' => $endDate
                    ],
                    [
                        'InstitutionStaff.end_date IS NULL',
                        'InstitutionStaff.start_date <=' => $endDate
                    ]
                ]
            ];
        }

        // Institution filter
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions[] = ['InstitutionStaff.institution_id' => $institutionId];
        }

        // Area filter
        $areaList = [];
        if ($areaLevelId > 1 && $areaId > 1
        ) {
            $areaList = $this->getAreaList($areaLevelId, $areaId);
        } elseif ($areaLevelId > 1) {

            $areaList = $this->getAreaList($areaLevelId,0);
        } elseif ($areaId > 1) {
            $areaList = $this->getAreaList(0,$areaId);
        }
        if (!empty($areaList)) {
            $conditions[] = ['Institutions.area_id IN' => $areaList];
        }
        // Main query
        $query
            ->select([
                'staff_id' => 'InstitutionStaff.staff_id',
                'staff_name' => $query->func()->concat([
                    'Users.first_name' => 'identifier',
                    $query->newExpr(" ' ' "),
                    'Users.last_name' => 'identifier'
                ]),
                'first_name' => 'Users.first_name',
                'last_name' => 'Users.last_name',
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'staff_position_name' => 'StaffPositionTitles.name',
                'staff_type_name' => 'StaffTypes.name',
                'openemisid' => 'Users.openemis_no',
                'identity_number' => 'Users.identity_number',
                'identity_type_id' => 'Users.identity_type_id',
                'identity_number' => 'Users.identity_number'
            ])
            ->from(['InstitutionStaff' => 'institution_staff'])
            ->join([
                'Institutions' => [
                    'table' => 'institutions',
                    'type' => 'INNER',
                    'conditions' => 'Institutions.id = InstitutionStaff.institution_id'
                ],
                'InstitutionPositions' => [
                    'table' => 'institution_positions',
                    'type' => 'INNER',
                    'conditions' => 'InstitutionPositions.id = InstitutionStaff.institution_position_id'
                ],
                'StaffPositionTitles' => [
                    'table' => 'staff_position_titles',
                    'type' => 'INNER',
                    'conditions' => 'StaffPositionTitles.id = InstitutionPositions.staff_position_title_id'
                ],
                'StaffTypes' => [
                    'table' => 'staff_types',
                    'type' => 'INNER',
                    'conditions' => 'StaffTypes.id = InstitutionStaff.staff_type_id'
                ],
                'Users' => [
                    'table' => 'security_users',
                    'type' => 'INNER',
                    'conditions' => 'Users.id = InstitutionStaff.staff_id'
                ],
                'StaffQualifications' => [
                    'table' => 'staff_qualifications',
                    'type' => 'LEFT',
                    'conditions' => 'StaffQualifications.staff_id = InstitutionStaff.staff_id'
                ]
            ])
            ->where(array_merge(
                ['StaffQualifications.id IS' => null], // Only staff without qualifications
                ...$conditions
            ))
            ->group(['InstitutionStaff.staff_id'])
            ->order(['Institutions.name' => 'ASC']);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) { 
                return $results->map(function ($row) { 
                    //For Default ID NO
                    $identity_typesTable = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
                    $identity_types = $identity_typesTable->find()
                                        ->where(['`default`' => 1])
                                        ->first();
                   $identity_type_id = $identity_types->id;
                   dump($row->identity_type_id ." == ". $identity_type_id);
                    if($row->identity_type_id == $identity_type_id){
                        dump(178);
                        $row['default_identity_type'] = $row->identity_number;
                    }else{
                        $row['default_identity_type'] = '';
                    }
                    return $row;
                });
            });

        // Access control
        if (!$superAdmin) {
            $query->find('ByAccess', [
                'user_id' => $userId,
                'institution_field_alias' => 'Institutions.id'
            ]);
        }
        
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields) 
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
            'key' => '',
            'field' => 'openemisid',
            'type' => 'string',
            'label' => 'OpenEMIS ID'
        ];
        
        $newFields[] = [
            'key' => 'staff_name',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => 'Staff'
        ];
        
        $newFields[] = [
            'key' => '',
            'field' => 'default_identity_type',
            'type' => 'string',
            'label' => 'Default Identity Number'
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
        
        $fields->exchangeArray($newFields);
    }

}