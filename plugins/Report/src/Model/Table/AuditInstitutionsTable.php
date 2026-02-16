<?php

namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;

class AuditInstitutionsTable extends AppTable
{
    public function initialize(array $config): void
    {

        $this->setTable('institution_activities');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('CreatedUser',  ['className' => 'User.Users', 'foreignKey' => 'created_user_id']);

        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);

        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {

        $requestData = json_decode($settings['process']['params']);

        $reportStartDate = (new DateTime($requestData->report_start_date))->format('Y-m-d H:i:s');
        $reportEndDate = (new DateTime($requestData->report_end_date))->format('Y-m-d H:i:s');

        $query
            ->select([
                'modified_on' => $this->aliasField('created'),
                'operation' => $this->aliasField('operation'),
                'field' => $this->aliasField('field'),
                'old_value' => $this->aliasField('old_value'),
                'new_value' => $this->aliasField('new_value'),
            ])
            ->contain([
                'CreatedUser' => [
                    'fields' => [
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
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

    public function onExcelGetCreatedName(EventInterface $event, Entity $entity)
    {
        return $entity->created_user->name;
    }

    public function onExcelGetCodeName(EventInterface $event, Entity $entity)
    {
        return $entity->institution->code_name;
    }
    //POCOR-9384 start
    public function onExcelGetModifiedOn(EventInterface $event, Entity $entity)
    {
        $modified_on = '';
        if ($entity->has('modified_on') && !empty($entity->modified_on)) {
            $modified_on = $entity->modified_on->format('jS M, Y');
        }
        return $modified_on;
    }
    //POCOR-9384 end
    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'modified_on', //POCOR-9384
            'field' => 'modified_on',
            'type' => 'string',
            'label' => __('Modified On')
        ];
        $newFields[] = [
            'key' => 'CreatedUser.name',
            'field' => 'created_name',
            'type' => 'string',
            'label' => __('Modified By')
        ];
        $newFields[] = [
            'key' => 'AuditInstitutions.operation',
            'field' => 'operation',
            'type' => 'string',
            'label' => __('Activity')
        ];
        $newFields[] = [
            'key' => 'AuditInstitutions.field',
            'field' => 'field',
            'type' => 'string',
            'label' => __('Field')
        ];
        $newFields[] = [
            'key' => 'AuditInstitutions.old_value',
            'field' => 'old_value',
            'type' => 'string',
            'label' => __('Original Value')
        ];
        $newFields[] = [
            'key' => 'AuditInstitutions.new_value',
            'field' => 'new_value',
            'type' => 'string',
            'label' => __('Modified Value')
        ];
        $newFields[] = [
            'key' => 'Institutions.code_name',
            'field' => 'code_name',
            'type' => 'string',
            'label' => __('Institution')
        ];

        $fields->exchangeArray($newFields);
    }
}
