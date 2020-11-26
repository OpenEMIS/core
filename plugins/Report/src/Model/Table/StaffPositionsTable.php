<?php
namespace Report\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class StaffPositionsTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institution_positions');
        parent::initialize($config);
        
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
        $this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'Security.Users']);
        $this->hasMany('InstitutionStaff', ['className' => 'Institution.Staff']);

        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
       $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $area_id = $requestData->area_id;
        $institution_id = $requestData->institution_id;

        if ($area_id == 0 && $institution_id != 0) {
            $where = ['Institutions.id' => $institution_id];
        } else if ($area_id != 0 && $institution_id != 0) {           
            $where = ['Institutions.id' => $institution_id,'Institutions.area_id' => $area_id]; 
        } else if ($area_id !=0 && $institution_id == 0) {
            $where = ['Institutions.area_id' => $area_id];
        } else {
            $where = [];
        }
        $InstitutionStaff = TableRegistry::get('Institution.InstitutionStaff');
        $Staff = TableRegistry::get('Security.Users');
        $Genders = TableRegistry::get('User.Genders');
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $UserIdentities = TableRegistry::get('User.Identities');
        
        $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',               
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'area_administratives_code' => 'AreaAdministratives.code',
                'area_administratives_name' => 'AreaAdministratives.name',
                'assignee_id' => 'Assignees.id',
                'workflow_steps_name' => 'Statuses.name',
                'position_no' => $this->aliasField('position_no'),
                'staff_position_grade_name' => 'StaffPositionGrades.name',
                'is_homeroom' => $this->aliasField('is_homeroom'),
                'openemis_no' => $Staff->aliasField('openemis_no'),
                'first_name' => $Staff->aliasField('first_name'),
                'last_name' => $Staff->aliasField('last_name'),
                'gender' => $Genders->aliasField('name'),
                'identity_type' => $IdentityTypes->aliasField('name'),
                'identity_number' => $UserIdentities->aliasField('number')
            ])
            ->contain([
                'Statuses' => [
                    'fields' => [
                        'Statuses.name'
                    ]
                ],
                'StaffPositionTitles' => [
                    'fields' => [
                        'StaffPositionTitles.id',
                        'StaffPositionTitles.name',
                        'StaffPositionTitles.type'
                    ]
                ],
                'StaffPositionGrades' => [
                    'fields' => [
                        'StaffPositionGrades.name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'Institutions.id',
                        'Institutions.name',
                        'Institutions.code'
                    ]
                ],
                'Institutions.Areas' => [
                    'fields' => [
                        'Areas.name',
                        'Areas.code'
                    ]
                ],
                'Institutions.AreaAdministratives' => [
                    'fields' => [
                        'AreaAdministratives.name',
                        'AreaAdministratives.code'
                    ]
                ],
                'Assignees' => [
                    'fields' => [
                        'Assignees.id',
                        'Assignees.first_name',
                        'Assignees.middle_name',
                        'Assignees.third_name',
                        'Assignees.last_name',
                        'Assignees.preferred_name'
                    ]
                ]
            ])
            ->leftJoin(
                    [$InstitutionStaff->alias() => $InstitutionStaff->table()],
                    [
                        $InstitutionStaff->aliasField('institution_position_id = ') . $this->aliasField('id'),
                        $InstitutionStaff->aliasField('institution_id = ') . $this->aliasField('institution_id')
                    ]
                )
            ->leftJoin(
                    [$Staff->alias() => $Staff->table()],
                    [
                        $Staff->aliasField('id = ') . $InstitutionStaff->aliasField('staff_id')
                    ]
                )
            ->leftJoin(
                    [$Genders->alias() => $Genders->table()],
                    [
                        $Genders->aliasField('id = ') . $Staff->aliasField('gender_id')
                    ]
                )
            ->leftJoin(
                    [$UserIdentities->alias() => $UserIdentities->table()],
                    [
                        $UserIdentities->aliasField('security_user_id = ') . $Staff->aliasField('id')
                    ]
                )
            ->leftJoin(
                    [$IdentityTypes->alias() => $IdentityTypes->table()],
                    [
                        $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id')
                    ]
                )
            ->where([$where])
            ->order(['institution_name', 'position_no']);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.code',
            'field' => 'area_administratives_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administratives_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];

        $newFields[] = [
            'key' => 'Assignees.id',
            'field' => 'assignee_id',
            'type' => 'string',
            'label' => __('Assignee')
        ];

        $newFields[] = [
            'key' => 'Statuses.name',
            'field' => 'workflow_steps_name',
            'type' => 'string',
            'label' => __('Status')
        ];

        $newFields[] = [
            'key' => 'InstitutionPositions.position_no',
            'field' => 'position_no',
            'type' => 'string',
            'label' => __('Number')
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.id',
            'field' => 'staff_position_id',
            'type' => 'string',
            'label' => __('Title')
        ];

        $newFields[] = [
            'key' => 'StaffPositionGrades.name',
            'field' => 'staff_position_grade_name',
            'type' => 'string',
            'label' => __('Grade')
        ];

        $newFields[] = [
            'key' => 'InstitutionPositions.is_homeroom',
            'field' => 'is_homeroom',
            'type' => 'string',
            'label' => __('Homeroom Teacher')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'first_name',
            'type' => 'string',
            'label' => __('First Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'last_name',
            'type' => 'string',
            'label' => __('Last Name')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Default Identity Type')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStaffPositionId(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('Staff.position_types');
        $staffPositionTitleType = '';

        if ($entity->has('staff_position_title')) {
            $staffPositionTitleType = $entity->staff_position_title->name;
            $staffType = $entity->staff_position_title->type;
            $type = array_key_exists($staffType, $options) ? $options[$staffType] : '';

            if (!empty($type)) {
                $staffPositionTitleType .= ' - ' . $type;
            }
        } else {
            Log::write('debug', $entity->name . ' has no staff_position_title...');
        }

        return $staffPositionTitleType;
    }

    public function onExcelGetInstitutionId(Event $event, Entity $entity)
    {
        return $entity->institution->code_name;
    }

    public function onExcelGetIsHomeroom(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno');
        return $options[$entity->is_homeroom];
    }

    public function onExcelGetStaffName(Event $event, Entity $entity)
    {
        if ($entity->has('_matchingData')) {
            return $entity->_matchingData['Users']->name;
        }
        return '';
    }
}
