<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;
use Cake\I18n\FrozenTime;
use Cake\I18n\FrozenDate;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\EventInterface;
/**
 * POCOR-9381
 * Develop Reports > Audit > Deleted Records report
 * This table maps to `deleted_records`. 
 * Generate xlsx report
 * */

class AuditDeletedRecordsTable extends AppTable
{ 
    public function initialize(array $config): void
    {
        $this->setTable('deleted_records');
        parent::initialize($config);
        
        $this->belongsTo('CreatedUser', [
            'className' => 'Security.Users',
            'foreignKey' => 'created_user_id'
        ]);
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);

        $referenceTable = $requestData->reference_table ?? null;
        $startDate      = $requestData->report_start_date ?? null;
        $endDate        = $requestData->report_end_date ?? null;

        // Convert dates
        $startDateObj = $this->parseDate($startDate);
        $endDateObj   = $this->parseDate($endDate) ?? FrozenDate::createFromFormat('d-m-Y', '31-12-' . date('Y'));

        // Base select
        $query
            ->select([
                'id',
                'reference_table',
                'reference_key',
                'data',
                'deleted_date',
                'created_user_id',
                'created',
                'created_user' => $query->func()->concat([
                                        'CreatedUser.first_name' => 'literal',
                                        ' ',
                                        'CreatedUser.last_name'  => 'literal'
                                    ])
            ])
            ->contain(['CreatedUser']);

        // Apply filters
        $query->where(function (QueryExpression $exp) use ($startDateObj, $endDateObj, $referenceTable) {
            $conditions = [];

            // Date range condition
            if ($startDateObj && $endDateObj) {
                $conditions[] = $exp
                    ->gte('deleted_date', $startDateObj->format('Ymd'))
                    ->lte('deleted_date', $endDateObj->format('Ymd'));
            }

            // Reference table condition
            if (!empty($referenceTable)) {
                $conditions[] = $exp->eq('reference_table', $referenceTable);
            }
            return $exp->and_($conditions);
        });

    }


    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'id',
            'field' => 'id',
            'type' => 'integer',
            'label' => __('ID')
        ];
        $newFields[] = [
            'key' => 'reference_table',
            'field' => 'reference_table',
            'type' => 'string',
            'label' => __('Reference Table')
        ];
         $newFields[] = [
            'key' => 'reference_key',
            'field' => 'reference_key',
            'type' => 'string',
            'label' => __('Reference Key')
        ];
        $newFields[] = [
            'key' => 'data',
            'field' => 'data',
            'type' => 'string',
            'label' => __('Data')
        ];
        $newFields[] = [
            'key' => 'deleted_date',
            'field' => 'deleted_date',
            'type' => 'string',
            'label' => __('Deleted Date')
        ];
        $newFields[] = [
            'key' => 'created_user',
            'field' => 'created_user',
            'type' => 'string',
            'label' => __('Created User')
        ];
        $newFields[] = [
            'key' => 'created',
            'field' => 'created',
            'type' => 'string',
            'label' => __('Created')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStartDate(EventInterface $event, Entity $entity) {
        if (!empty($entity->start_date)) {
            return $this->formatDate($entity->start_date);
        }
    }
    public function onExcelGetEndDate(EventInterface $event, Entity $entity) {
        if (!empty($entity->end_date)) {
            return $this->formatDate($entity->end_date);
        }
    }
    public function onExcelGetCreated(EventInterface $event, Entity $entity) {
        if (!empty($entity->created)) {
            return $this->formatDate($entity->created);
        }
    }

    public function onExcelGetSecurityRole(EventInterface $event, Entity $entity)
    {
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $SecurityRoles = TableRegistry::get('Security.SecurityRoles');

        $obj = [];
        if (!empty($entity->security_group_user_id )) {
            $groupUsers = $SecurityGroupUsers->find()
                ->where([
                    'SecurityGroupUsers.security_user_id' => $entity->staff_id 
                ])
                ->contain(['SecurityRoles'])
                ->all();

            foreach ($groupUsers as $groupUser) {
                if (!empty($groupUser->security_role_id)) {
                    $role = $SecurityRoles->find()
                        ->where(['id' => $groupUser->security_role_id])
                        ->first();

                    if ($role) {
                        $obj[] = $role->name;
                    }
                }
            }
        }

        $values = !empty($obj) ? implode(', ', array_unique($obj)) : __('');
        return $values;
    }


    private function parseDate(?string $date): ?FrozenDate
    {
        if (empty($date)) {
            return null;
        }

        // with time first
        $dt = FrozenDate::createFromFormat('d-m-Y H:i:s', $date);
        if ($dt) {
            return $dt;
        }

        // without time
        return FrozenDate::createFromFormat('d-m-Y', $date);
    }

}
