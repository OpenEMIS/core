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

class AuditSecuritiesRolesPermissionsTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('security_role_functions');
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
        // POCOR-8909 start
        $query
            ->select([
                '`Security_Role`' => 'SecurityRoles.name',
                '`Security_Module`' => 'SecurityFunctions.module',
                '`Security_Category`' => 'SecurityFunctions.category',
                '`Security_Function`' => 'SecurityFunctions.name',
                '`View`' => "(CASE WHEN AuditSecuritiesRolesPermissions._view = 1 THEN 'Yes' ELSE 'No' END)",
                '`Edit`' => "(CASE WHEN AuditSecuritiesRolesPermissions._edit = 1 THEN 'Yes' ELSE 'No' END)",
                '`Add`' => "(CASE WHEN AuditSecuritiesRolesPermissions._add = 1 THEN 'Yes' ELSE 'No' END)",
                '`Delete`' => "(CASE WHEN AuditSecuritiesRolesPermissions._delete = 1 THEN 'Yes' ELSE 'No' END)",
                '`Execute`' => "(CASE WHEN AuditSecuritiesRolesPermissions._execute = 1 THEN 'Yes' ELSE 'No' END)",
            ])
            ->innerJoin(['SecurityFunctions' => 'security_functions'], [
                [
                    'SecurityFunctions.id = AuditSecuritiesRolesPermissions.security_function_id',
                ]
            ])
            ->innerJoin(['SecurityRoles' => 'security_roles'], [
                [
                    'SecurityRoles.id = AuditSecuritiesRolesPermissions.security_role_id',
                ]
            ])
            ->where([
                $this->aliasField('created >= "') . $reportStartDate . '"',
                $this->aliasField('created <= "') . $reportEndDate . '"'
            ])
            ->order(['SecurityRoles.name' => 'ASC',
                'SecurityFunctions.module' => 'ASC',
                'SecurityFunctions.category' => 'ASC',
                'SecurityFunctions.name' => 'ASC']);
            // POCOR-8909 end
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        // POCOR-8909 start
        $newFields[] = [
            'key' => 'AuditSecuritiesRolesPermissions.Security_Role',
            'field' => 'Security_Role',
            'type' => 'string',
            'label' => __('Security Role')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesRolesPermissions.Security_Module',
            'field' => 'Security_Module',
            'type' => 'string',
            'label' => __('Security Module')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesRolesPermissions.Security_Category',
            'field' => 'Security_Category',
            'type' => 'string',
            'label' => __('Security Category')
        ];
        // POCOR-8909 end
        $newFields[] = [
            'key' => 'AuditSecuritiesRolesPermissions.Security_Function',
            'field' => 'Security_Function',
            'type' => 'string',
            'label' => __('Security Function')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesRolesPermissions.View',
            'field' => 'View',
            'type' => 'string',
            'label' => __('View')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesRolesPermissions.Edit',
            'field' => 'Edit',
            'type' => 'string',
            'label' => __('Edit')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesRolesPermissions.Add',
            'field' => 'Add',
            'type' => 'string',
            'label' => __('Add')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesRolesPermissions.Delete',
            'field' => 'Delete',
            'type' => 'string',
            'label' => __('Delete')
        ];
        $newFields[] = [
            'key' => 'AuditSecuritiesRolesPermissions.Execute',
            'field' => 'Execute',
            'type' => 'string',
            'label' => __('Execute')
        ];

        $fields->exchangeArray($newFields);
    }

}
