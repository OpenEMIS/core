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

class AuditUserTable extends AppTable
{
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
        
        $reportStartDate = (new DateTime($requestData->report_start_date))->format('Y-m-d');
        $reportEndDate = (new DateTime($requestData->report_end_date))->format('Y-m-d');

        $query
            ->select([
                'created' => $this->aliasField('created'),
                'field' => $this->aliasField('field'),
                'old_value' => $this->aliasField('old_value'),
                'new_value' => $this->aliasField('new_value'),
                'is_student' => 'Users.is_student',
                'is_staff' => 'Users.is_staff',
                'is_guardian' => 'Users.is_guardian',
                'openemis_no' => 'Users.openemis_no',
                'ModifiedBy' => $query->func()->concat([
                    'CreatedUser.first_name' => 'literal',
                    ' ',
                    'CreatedUser.last_name' => 'literal'
                ]),
                'ModifiedOn' => $query->func()->concat([
                    $this->aliasField('created') => 'literal'
                ]),
                'UserFirstLastName' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    ' ',
                    'Users.last_name' => 'literal'
                ])
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
            ]);

            switch ($requestData->user_type) {
            case 1:
                $query->where([
                    $this->aliasField('Users.is_student = "') . 1 . '"',
                    $this->aliasField('created >= "') . $reportStartDate . '"',
                    $this->aliasField('created <= "') . $reportEndDate . '"'
                ]);
                break;
            case 2:
                $query->where([
                    $this->aliasField('Users.is_staff = "') . 1 . '"',
                    $this->aliasField('created >= "') . $reportStartDate . '"',
                    $this->aliasField('created <= "') . $reportEndDate . '"'
                ]);
                break;
            case 3:
                $query->where([
                    $this->aliasField('Users.is_guardian = "') . 1 . '"',
                    $this->aliasField('created >= "') . $reportStartDate . '"',
                    $this->aliasField('created <= "') . $reportEndDate . '"'
                ]);
                break;
            default:
                $query->where([
                    $this->aliasField('created >= "') . $reportStartDate . '"',
                    $this->aliasField('created <= "') . $reportEndDate . '"'
                ]);
                break;
        } 
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[0] = [
            'key' => 'created',
            'field' => 'ModifiedOn',
            'type' => 'string',
            'label' => 'Modified On'
        ];
        $newFields[1] = [
            'key' => 'CreatedUser.First_Last_Name',
            'field' => 'ModifiedBy',
            'type' => 'string',
            'label' => 'Modified By'
        ];
        $newFields[6] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'openemis_no'  
        ];
        $newFields[7] = [
            'key' => 'Users.First_Last_Name',
            'field' => 'UserFirstLastName',
            'type' => 'string',
            'label' => 'Name'  
        ];
        $newFields[8] = [
            'key' => 'Users.isStaff',
            'field' => 'is_staff',
            'type' => 'string',
            'label' => ''  
        ];
        $newFields[9] = [
            'key' => 'Users.isStudent',
            'field' => 'is_student',
            'type' => 'string',
            'label' => ''  
        ];
        $newFields[10] = [
            'key' => 'Users.isGuardian',
            'field' => 'is_guardian',
            'type' => 'string',
            'label' => ''  
        ];

        foreach ($fields as $currentIndex => $value) {
            switch ($value['field']) {
                case "operation":
                    $newFields[2] = $value;
                    $newFields[2]['label'] = "Activity";
                    break;
                case "field":
                    $newFields[3] = $value;
                    $newFields[3]['label'] = "Field";
                    break;
                case "old_value":
                    $newFields[4] = $value;
                    $newFields[4]['label'] = "Original Value";
                    break;
                case "new_value":
                    $newFields[5] = $value;
                    $newFields[5]['label'] = "Modified Value";
                    break;
                default:
                    break;
            }
        }

        ksort($newFields);
        $fields->exchangeArray($newFields);
    }
}
