<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use ArrayObject;

class InstitutionSubjectStaffTable extends AppTable
{
    use OptionsTrait;
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index']
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Staff.afterSave'] = 'staffAfterSave';
        return $events;
    }

    public function addStaffToSubject($staffId, $institutionSubjectId, $institutionId)
    {
        $result = false;
        $existingRecord = $this->find()
            ->where([
                $this->aliasField('staff_id') => $staffId,
                $this->aliasField('institution_subject_id') => $institutionSubjectId
            ])
            ->first();

        if (empty($existingRecord)) {
            $todayDate = Time::now()->format('Y-m-d');

            $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
            $institutionStaff = $InstitutionStaffTable
                                ->find()
                                ->where([
                                    $InstitutionStaffTable->aliasField('staff_id') => $staffId,
                                    $InstitutionStaffTable->aliasField('institution_id') => $institutionId
                                ])
                                ->first();

            $endDate = null;
            if ($institutionStaff->end_date) {
                $endDate = $institutionStaff->end_date->format('Y-m-d');
            }

            $entity = $this->newEntity([
                'id' => Text::uuid(),
                'start_date' => $todayDate,
                'end_date' => $endDate, //institution_staff end_date as default value.
                'staff_id' => $staffId,
                'institution_id' => $institutionId,
                'institution_subject_id' => $institutionSubjectId
            ]);
            $result = $this->save($entity);
        } else {
            $result = $existingRecord;
        }

        return $result;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $entity->start_date = Time::now();
        }
    }

    public function removeStaffFromSubject($staffId, $institutionSubjectId)
    {
        $result = false;
        $existingRecords = $this->find()
            ->where([
                $this->aliasField('staff_id') => $staffId,
                $this->aliasField('institution_subject_id') => $institutionSubjectId
            ])
            ->toArray();

        $deleteCount = 0;
        if (!empty($existingRecords)) {
            foreach ($existingRecords as $key => $value) {
                if ($this->delete($value)) {
                    $deleteCount++;
                }
            }
        }

        return $deleteCount;
    }

    public function staffAfterSave(Event $event, $staff)
    {
        $StaffStatusesTable = TableRegistry::get('Staff.StaffStatuses');
        $InstitutionStaff = TableRegistry::get('Institution.Staff');

        // if ($staff->dirty('end_date')) {
            $selectConditions = [];
            if ($staff->isNew()) {
                $selectConditions = [
                    $InstitutionStaff->aliasField('id') => $staff->id,
                    $InstitutionStaff->aliasField('staff_status_id') => $StaffStatusesTable->getIdByCode('ASSIGNED')
                ];
            } else {
                $selectConditions = ['Users.id' => $staff->staff_id];
            }

            //get the entire information of the staff
            $StaffData = $InstitutionStaff->find()
                ->select([
                    $InstitutionStaff->aliasField('FTE'),
                    $InstitutionStaff->aliasField('start_date'),
                    $InstitutionStaff->aliasField('end_date'),
                    'Users.id',
                    'Users.openemis_no',
                    'Users.first_name',
                    'Users.middle_name',
                    'Users.third_name',
                    'Users.last_name',
                    'Institutions.code',
                    'Institutions.name',
                    'Positions.id',
                    'Positions.position_no',
                    'StaffPositionTitles.name',
                    'StaffPositionTitles.type',
                    'StaffTypes.name',
                    'StaffStatuses.code',
                    'StaffStatuses.name'
                ])
                ->find('byInstitution', ['Institutions.id' => $staff->institution_id])
                ->contain([
                    'Users',
                    'Institutions',
                    'Positions.StaffPositionTitles',
                    'StaffTypes',
                    'StaffStatuses'
                ])
                ->where($selectConditions)
                ->toArray();

            $updateEndDate = false;

            // use case: Teacher holding one teaching position, teaching position will be ended
            // expected: Teaching subject will be ended based on the position
            if (count($StaffData) == 1) {
                if ($StaffData[0]->position->staff_position_title->type == 1) { //if teaching position
                    $updateEndDate = true;
                    $endDate = $staff->end_date;
                }
            } else {
                // use case: Teacher holding one teaching position and one non-teaching position, teaching position will be ended
                // expected: Teaching subject will be ended based on the teaching position
                $endDate = '';
                foreach ($StaffData as $key => $value) { //loop through position
                    if ($value->position->staff_position_title->type == 1) { //if teaching position
                        $updateEndDate = true;

                        if (is_null($value->end_date)) { //if null, then always get it.
                            $endDate = $value->end_date;
                            break;
                        } else {
                            if (!empty($endDate)) {
                                if ($endDate < $value->end_date) {
                                    $endDate = $value->end_date;
                                }
                            } else {
                                $endDate = $value->end_date;
                            }
                        }
                    }
                }
            }

            $updateConditions = [];
            if ($updateEndDate) {
                $updateConditions = [
                    'staff_id' => $staff->staff_id,
                    'institution_id' => $staff->institution_id
                ];

                if ($staff->isNew()) {
                    if (!is_null($endDate)) {
                        $updateConditions['AND'] = [
                            'end_date IS NOT NULL',
                            'end_date > ' => $staff->start_date->format('Y-m-d'),
                            'end_date < ' => $endDate->format('Y-m-d')
                        ];
                    } else {
                        $endDate = null;
                        $updateConditions ['end_date > '] = $staff->start_date->format('Y-m-d');
                    }
                }

                $this->updateAll(
                    ['end_date' => $endDate],
                    $updateConditions
                );
            }
        // }
    }

    public function findSubjectEditPermission(Query $query, array $options)
    {
        $subjectId = $options['subject_id'];
        $academicPeriodId = $options['academic_period_id'];
        $institutionId = $options['institution_id']; // current institution POCOR-4981
        $userId = $options['user']['id']; // current user
        
        if ($options['user']['super_admin'] == 0) { // if he is not super admin
            $allSubjectPermission = $this->getRoleEditPermissionAccessForAllSubjects($userId, $institutionId); //POCOR-4983
            $query
                ->find('bySecurityAccess')
                ->matching('InstitutionSubjects', function ($q) use (
                    $subjectId, 
                    $academicPeriodId, 
                    $institutionId, 
                    $allSubjectPermission) {
                    
                    if($allSubjectPermission) {
                        return $q->where([
                            'InstitutionSubjects.academic_period_id' => $academicPeriodId,
                            'InstitutionSubjects.institution_id' => $institutionId // POCOR-4981
                        ]);
                    } else {
                        return $q->where([
                            'InstitutionSubjects.education_subject_id' => $subjectId,
                            'InstitutionSubjects.academic_period_id' => $academicPeriodId,
                            'InstitutionSubjects.institution_id' => $institutionId // POCOR-4981
                        ]);
                    }
                    
                })
                ->where([
                    $this->aliasField('staff_id') => $userId
                ])
                ->group([$this->aliasField('staff_id')]);

            $query
                ->find('bySecurityRoleAccess');
        }
        
        // POCOR-4981
        if( isset($institutionId) 
            && $institutionId > 0 
            && $options['user']['super_admin'] == 1) // if he is super admin
        {
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }
    }

    public function findBySecurityAccess(Query $query, array $options)
    {
        if (array_key_exists('id', $options['user'])) {
            $userId = $options['user']['id'];

            $Institutions = TableRegistry::get('Institution.Institutions');

            $institutionQuery = $Institutions->find()
                ->select([
                    'institution_id' => $Institutions->aliasField('id'),
                    'security_group_id' => 'SecurityGroupUsers.security_group_id',
                    'security_role_id' => 'SecurityGroupUsers.security_role_id'
                ])
                ->innerJoin(['SecurityGroupInstitutions' => 'security_group_institutions'], [
                    ['SecurityGroupInstitutions.institution_id = ' . $Institutions->aliasField('id')]
                ])
                ->innerJoin(['SecurityGroupUsers' => 'security_group_users'], [
                    [
                        'SecurityGroupUsers.security_group_id = SecurityGroupInstitutions.security_group_id',
                        'SecurityGroupUsers.security_user_id = ' . $userId
                    ]
                ])
                ->group([$Institutions->aliasField('id'), 'SecurityGroupUsers.security_group_id', 'SecurityGroupUsers.security_role_id']);

            /* Generated SQL: */

            // SELECT institutions.id AS institution_id, security_group_users.security_group_id, security_group_users.security_role_id
            // FROM institutions
            // INNER JOIN security_group_institutions ON security_group_institutions.institution_id = institutions.id
            // INNER JOIN security_group_users
            //     ON security_group_users.security_group_id = security_group_institutions.security_group_id
            //     AND security_group_users.security_user_id = 4
            // GROUP BY institutions.id, security_group_users.security_group_id, security_group_users.security_role_id


            $areaQuery = $Institutions->find()
                ->select([
                    'institution_id' => $Institutions->aliasField('id'),
                    'security_group_id' => 'SecurityGroupUsers.security_group_id',
                    'security_role_id' => 'SecurityGroupUsers.security_role_id'
                ])
                ->innerJoin(['Areas' => 'areas'], ['Areas.id = ' . $Institutions->aliasField('area_id')])
                ->innerJoin(['AreasAll' => 'areas'], [
                    'AreasAll.lft <= Areas.lft',
                    'AreasAll.rght >= Areas.rght'
                ])
                ->innerJoin(['SecurityGroupAreas' => 'security_group_areas'], [
                    'SecurityGroupAreas.area_id = AreasAll.id'
                ])
                ->innerJoin(['SecurityGroupUsers' => 'security_group_users'], [
                    [
                        'SecurityGroupUsers.security_group_id = SecurityGroupAreas.security_group_id',
                        'SecurityGroupUsers.security_user_id = ' . $userId
                    ]
                ])
                ->group([$Institutions->aliasField('id'), 'SecurityGroupUsers.security_group_id', 'SecurityGroupUsers.security_role_id']);

            /* Generated SQL: */

            // SELECT institutions.id AS institution_id, security_group_users.security_group_id, security_group_users.security_role_id
            // FROM institutions
            // INNER JOIN areas ON areas.id = institutions.area_id
            // INNER JOIN areas AS AreaAll
            //     ON AreaAll.lft <= areas.lft
            //     AND AreaAll.rght >= areas.rght
            // INNER JOIN security_group_areas ON security_group_areas.area_id = AreaAll.id
            // INNER JOIN security_group_users
            //     ON security_group_users.security_group_id = security_group_areas.security_group_id
            //     AND security_group_users.security_user_id = 4
            // GROUP BY institutions.id, security_group_users.security_group_id, security_group_users.security_role_id

            $query->join([
                'table' => '((' . $institutionQuery->sql() . ' ) UNION ( ' . $areaQuery->sql() . '))', // inner join subquery
                'alias' => 'SecurityAccess',
                'type' => 'inner',
                'conditions' => ['SecurityAccess.institution_id = ' . $this->aliasField('institution_id')]
            ]);
        }

        return $query;
    }

    public function findBySecurityRoleAccess(Query $query, array $options)
    {
        // This logic is dependent on SecurityAccessBehavior because it relies on SecurityAccess join table
        // This logic will only be triggered when the table is accessed by RestfulController

        if (array_key_exists('user', $options) && is_array($options['user'])) { // the user object is set by RestfulComponent
            $user = $options['user'];
            if ($user['super_admin'] == 0) { // if he is not super admin
                $userId = $user['id'];
                $today = Date::now();

                $query->innerJoin(['SecurityRoleFunctions' => 'security_role_functions'], [
                    'SecurityRoleFunctions.security_role_id = SecurityAccess.security_role_id',
                    'SecurityRoleFunctions.`_view` = 1' // check if the role have view access
                ])                
                ->innerJoin(['SecurityFunctions' => 'security_functions'], [
                    'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    "SecurityFunctions.controller = 'Institutions'" // only restricted to permissions of Institutions
                ])
                ->leftJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                    'InstitutionClassSubjects.institution_subject_id = InstitutionSubjectStaff.institution_subject_id'
                ])
                ->where([
                    // basically if AllSubjects permission is granted, the user should see all subjects of that classes
                    // if MySubjects permission is granted, the user must be a teacher of that subject
                    'OR' => [
                        [
                            'OR' => [ // AllSubjects permissions
                                "SecurityFunctions.`_view` LIKE '%AllSubjects.index%'",
                                "SecurityFunctions.`_view` LIKE '%AllSubjects.view%'"
                            ]
                        ], [
                            'AND' => [
                                [
                                    'OR' => [ // MySubjects permissions
                                        "SecurityFunctions.`_view` LIKE '%Subjects.index%'",
                                        "SecurityFunctions.`_view` LIKE '%Subjects.view%'"
                                    ]
                                ],
                                // 'InstitutionSubjectStaff.institution_subject_id = InstitutionSubjects.id',
                                'InstitutionSubjectStaff.institution_subject_id = InstitutionSubjectStaff.institution_subject_id',
                                'InstitutionSubjectStaff.staff_id' => $userId,
                                'OR' => [
                                    'InstitutionSubjectStaff.end_date IS NULL',
                                    'InstitutionSubjectStaff.end_date >= ' => $today->format('Y-m-d')
                                ]
                            ]
                        ], [
                            'AND' => [
                                [
                                    'OR' => [
                                        "SecurityFunctions.`_view` LIKE '%Classes.index%'",
                                        "SecurityFunctions.`_view` LIKE '%Classes.view%'"
                                    ]
                                ], [
                                    'EXISTS (
                                        SELECT 1
                                        FROM institution_class_subjects
                                        JOIN institution_classes
                                        ON institution_classes.id = institution_class_subjects.institution_class_id
                                        JOIN institution_classes_secondary_staff ON
                                        institution_classes_secondary_staff.institution_class_id = institution_classes.id
                                        AND (institution_classes.staff_id = "' . $userId . '" OR institution_classes_secondary_staff.secondary_staff_id = "' . $userId . '")
                                        WHERE institution_class_subjects.institution_subject_id = InstitutionSubjectStaff.institution_subject_id
                                        LIMIT 1
                                    )'
                                ]
                            ]
                        ]
                    ]
                ])
                ->group([$this->aliasField('id')]); // so it doesn't show duplicate subjects
            }
        }
    }
    
    /*
     * Function Name: getRoleEditPermissionAccessForAllSubjects
     * Parameters : userId, institutionId
     * JIRA ISSUE: POCOR-4983
     * Purpose: Any role have permission to edit all subjects marks of the assessment
     * Date: 26 June 2019
    */
    
    public function getRoleEditPermissionAccessForAllSubjects($userId, $institutionId)
    {
        $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId); 
        $userAccessRoles = implode(', ', $roles);
        
        $QueryResult = TableRegistry::get('SecurityRoleFunctions')->find()              
                ->innerJoin(['SecurityFunctions' => 'security_functions'], [
                    [
                        'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    ]
                ])
                ->where([
                    'SecurityFunctions.controller' => 'Institutions',
                    'SecurityRoleFunctions.security_role_id IN'=>$userAccessRoles,
                    'AND' => [ 'OR' => [ 
                                        "SecurityFunctions.`_view` LIKE '%AllSubjects.index%'",
                                        "SecurityFunctions.`_view` LIKE '%AllSubjects.view%'"
                                    ]
                              ],
                    'SecurityRoleFunctions._view' => 1,
                    'SecurityRoleFunctions._edit' => 1
                ])
                ->toArray();
       
        if(!empty($QueryResult)){
            return true;
        }
          
        return false;
    }
    
}
