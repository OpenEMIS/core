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

class AuditUsersTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('user_activities');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey'=>'security_user_id']);
        $this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
        
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
                'modified_on' => $this->aliasField('created'),
                'field' => $this->aliasField('field'),
                'old_value' => $this->aliasField('old_value'),
                'new_value' => $this->aliasField('new_value'),
                'is_student' => 'Users.is_student',
                'is_staff' => 'Users.is_staff',
                'is_guardian' => 'Users.is_guardian',
                'openemis_no' => 'Users.openemis_no',
                'activity' => $this->aliasField('operation')
            ])
            ->contain([
                'CreatedUser' => [
                    'fields' => [
                        'first_name',
                        'last_name'
                    ]
                ]
            ])
            ->contain([
                'Users' => [
                    'fields' => [
                        'first_name',
                        'last_name'
                    ]
                ]
            ])
            ->where([
                $this->aliasField('created >= "') . $reportStartDate . '"',
                $this->aliasField('created <= "') . $reportEndDate . '"'
            ]);

        switch ($requestData->user_type) {
            case UserTypeSelected::STUDENT:
                $query->where([
                    'Users.is_student' => 1
                ]);
                break;
            case UserTypeSelected::STAFF:
                $query->where([
                    'Users.is_staff' => 1
                ]);
                break;
            case UserTypeSelected::GUARDIAN:
                $query->where([
                    'Users.is_guardian' => 1
                ]);
                break;
            default:    
                //UserTypeSelected::ALL
                break;
        } 
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'created',
            'field' => 'modified_on',
            'type' => 'string',
            'label' => __('Modified On')
        ];
        $newFields[] = [
            'key' => 'CreatedUser.First_Last_Name',
            'field' => 'modified_by',
            'type' => 'string',
            'label' => __('Modified By')
        ];
        $newFields[] = [
            'key' => 'AuditUsers.operation',
            'field' => 'activity',
            'type' => 'string',
            'label' => __('Activity')
        ];
        $newFields[] = [
            'key' => 'AuditUsers.field',
            'field' => 'field',
            'type' => 'string',
            'label' => __('Field')
        ];
        $newFields[] = [
            'key' => 'AuditUsers.old_value',
            'field' => 'old_value',
            'type' => 'string',
            'label' => __('Original Value')
        ];
        $newFields[] = [
            'key' => 'AuditUsers.new_value',
            'field' => 'new_value',
            'type' => 'string',
            'label' => __('Modified Value')
        ];
        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('Openemis ID')  
        ];
        $newFields[] = [
            'key' => 'Users.First_Last_Name',
            'field' => 'user_first_last_name',
            'type' => 'string',
            'label' => __('Name')
        ];
        $newFields[] = [
            'key' => 'Users.isStaff',
            'field' => 'is_staff',
            'type' => 'string'
        ];
        $newFields[] = [
            'key' => 'Users.isStudent',
            'field' => 'is_student',
            'type' => 'string'
        ];
        $newFields[] = [
            'key' => 'Users.isGuardian',
            'field' => 'is_guardian',
            'type' => 'string'
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetIsStaff(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno');

        if (array_key_exists($entity->is_staff, $options)) {
            return $options[$entity->is_staff];
        }

        return '';
    }

    public function onExcelGetIsGuardian(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno');

        if (array_key_exists($entity->is_guardian, $options)) {
            return $options[$entity->is_guardian];
        }

        return '';
    }

    public function onExcelGetIsStudent(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno');

        if (array_key_exists($entity->is_student, $options)) {
            return $options[$entity->is_student];
        }

        return '';
    }

    public function onExcelGetModifiedBy(Event $event, Entity $entity)
    {
        return $entity->created_user->name;
    }

    public function onExcelGetUserFirstLastName(Event $event, Entity $entity)
    {
        return $entity->user->name;
    }
}
