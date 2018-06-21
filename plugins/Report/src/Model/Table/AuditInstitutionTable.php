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

class AuditInstitutionTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_activities');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey'=>'institution_id']);
        $this->belongsTo('CreatedUser',  ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
        
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
                'operation' => $this->aliasField('operation'),
                'field' => $this->aliasField('field'),
                'old_value' => $this->aliasField('old_value'),
                'new_value' => $this->aliasField('new_value'),
                'ModifiedBy' => $query->func()->concat([
                    'CreatedUser.first_name' => 'literal',
                    ' ',
                    'CreatedUser.last_name' => 'literal'
                ]),
                'ModifiedOn' => $query->func()->concat([
                    $this->aliasField('created') => 'literal'
                ]),
                'InstitutionNamePlusCode' => $query->func()->concat([
                    'Institutions.code' => 'literal',
                    ' - ',
                    'Institutions.name' => 'literal'
                ])
                // 'first_name' => 'CreatedUser.first_name',
                // 'last_name' => 'CreatedUser.last_name',
                // 'name' => 'Institutions.name',
                // 'code' => 'Institutions.code',
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
                'Institutions' => [
                    'fields' => [
                        'name',
                        'code'
                    ]
                ]
            ])
            ->where([
                $this->aliasField('created >= "') . $reportStartDate . '"',
                $this->aliasField('created <= "') . $reportEndDate . '"'
            ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        $newFields = [];

        $newFields[0] = [
            'key' => 'Institutions.created',
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
            'key' => 'Institutions.Name_Code',
            'field' => 'InstitutionNamePlusCode',
            'type' => 'string',
            'label' => 'Institution'
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
