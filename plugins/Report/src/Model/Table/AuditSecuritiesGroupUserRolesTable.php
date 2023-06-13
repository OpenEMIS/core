<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;

use App\Model\Traits\OptionsTrait;
use Directory\Model\Table\DirectoriesTable as UserTypeSelected;

class AuditSecuritiesGroupUserRolesTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('security_group_users');
        parent::initialize($config);
        // $this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
        
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);

        $this->addBehavior('Report.ReportList');
    }



    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
       
        $reportStartDate = (new DateTime($requestData->report_start_date))->format('Y-m-d H:i:s');
        $reportEndDate = (new DateTime($requestData->report_end_date))->format('Y-m-d H:i:s');
        $query
            ->select([
                'security_group_name' => "security_groups.name",
                'security_group_type' => "(IF(institutions.id IS NULL, 'User Group', 'System Group'))",
                'security_group_areas' => "(IFNULL(area_groups.area_name, ''))",
                'security_group_institutions' => "(IFNULL(institution_groups.institution_name, ''))",
                'security_role' => "security_roles.name",
                'security_role_type' => "(IF(security_roles.security_group_id = -1, 'System Role', 'User Role'))",
                'openemis_no' => "security_users.openemis_no",
                'user_name' => "CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name)",
            ])
            ->innerJoin(['security_users' => 'security_users'], [
                [
                    'security_users.id = AuditSecuritiesGroupUserRoles.security_user_id',
                ]
            ])
            ->innerJoin(['security_groups' => 'security_groups'], [
                [
                    'security_groups.id = AuditSecuritiesGroupUserRoles.security_group_id',
                ]
            ])
            ->innerJoin(['security_roles' => 'security_roles'], [
                [
                    'security_roles.id = AuditSecuritiesGroupUserRoles.security_role_id',
                ]
            ])
            ->innerJoin(['institutions' => 'institutions'], [
                [
                    'institutions.security_group_id = AuditSecuritiesGroupUserRoles.security_group_id',
                ]
            ])
            ->leftJoin(['area_groups' => '(SELECT security_group_areas.security_group_id
            ,GROUP_CONCAT(DISTINCT(areas.name)) area_name FROM security_group_areas INNER JOIN areas ON areas.id = security_group_areas.area_id GROUP BY security_group_areas.security_group_id)'], [
                [
                    'area_groups.security_group_id = AuditSecuritiesGroupUserRoles.security_group_id',
                ]
            ])
            ->leftJoin(['institution_groups' => '(SELECT security_group_institutions.security_group_id
            ,GROUP_CONCAT(DISTINCT(institutions.name)) institution_name FROM security_group_institutions INNER JOIN institutions ON institutions.id = security_group_institutions.institution_id GROUP BY security_group_institutions.security_group_id)'], [
                [
                    'institution_groups.security_group_id = AuditSecuritiesGroupUserRoles.security_group_id',
                ]
            ])
            ->where([
                $this->aliasField('created >= "') . $reportStartDate . '"',
                $this->aliasField('created <= "') . $reportEndDate . '"'
            ])
            ->group(['AuditSecuritiesGroupUserRoles.security_group_id'
            ,'AuditSecuritiesGroupUserRoles.security_user_id'
            ,'AuditSecuritiesGroupUserRoles.security_role_id'])
            ->order(['security_users.openemis_no']);

            // echo '<pre>';
            // print_r($query); die;

    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'AuditSecuritiesGroupUserRoles.security_group_name',
            'field' => 'security_group_name',
            'type' => 'string',
            'label' => __('Security Group Name')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesGroupUserRoles.security_group_type',
            'field' => 'security_group_type',
            'type' => 'string',
            'label' => __('Security Group Type')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesGroupUserRoles.security_group_areas',
            'field' => 'security_group_areas',
            'type' => 'string',
            'label' => __('Security Group Areas')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesGroupUserRoles.security_group_institutions',
            'field' => 'security_group_institutions',
            'type' => 'string',
            'label' => __('Security Group Institutions')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesGroupUserRoles.security_role',
            'field' => 'security_role',
            'type' => 'string',
            'label' => __('Security Role')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesGroupUserRoles.security_role_type',
            'field' => 'security_role_type',
            'type' => 'string',
            'label' => __('Security Role Type')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesGroupUserRoles.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS No.')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesGroupUserRoles.user_name',
            'field' => 'user_name',
            'type' => 'string',
            'label' => __('User Name')
        ];

        $fields->exchangeArray($newFields);
    }

}
