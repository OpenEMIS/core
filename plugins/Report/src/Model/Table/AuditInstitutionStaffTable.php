<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
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
/**
 * POCOR-9383
 * Develop institution staff audit report
 * This table maps to `institution_staff`. 
 * Generate xlsx report
 * */

class AuditInstitutionStaffTable extends AppTable
{ 
    public function initialize(array $config): void
    {
        $this->setTable('institution_staff');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades', 'foreignKey' => 'staff_position_grade_id']);
        $this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);
        $this->hasMany('StaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles', 'foreignKey' => 'institution_staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffTransferOut', ['className' => 'Institution.StaffTransferOut', 'foreignKey' => 'previous_institution_staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        
        $this->belongsTo('ModifiedUser', [
            'className' => 'Security.Users',
            'foreignKey' => 'modified_user_id'
        ]);
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

        $startDate   = $requestData->report_start_date ?? null;
        $endDate     = $requestData->report_end_date ?? null;
        //Apply  start_date and end_date filters
        $startDateObj = $this->parseDate($startDate);
        $endDateObj   = $this->parseDate($endDate) ??FrozenDate::createFromFormat('d-m-Y', '31-12-' . date('Y'));

        $query->where(function (QueryExpression $exp) use ($startDateObj, $endDateObj) {
        return $exp
            ->gte($this->aliasField('start_date'), $startDateObj->format('Y-m-d'))
            ->lte($this->aliasField('start_date'), $endDateObj->format('Y-m-d'));
        });
        //Select only required fields
        $query
            ->select([
                'institution_code'   => 'Institutions.code',
                'institution_name'   => 'Institutions.name',
                'openemis_no'      => 'Users.openemis_no',
                'staff_name'      => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                ]),
                'staff_status'       => 'StaffStatuses.name',
                'staff_type'         => 'StaffTypes.name',
                'institution_position' => 'Positions.position_no',
                'staff_position_grade' => 'StaffPositionGrades.name',
                'is_homeroom'        => $this->aliasField('is_homeroom'),
                'fte'                => $this->aliasField('fte'),
                'start_date'         => $this->aliasField('start_date'),
                'start_year'         => $this->aliasField('start_year'),
                'end_date'           => $this->aliasField('end_date'),
                'end_year'           => $this->aliasField('end_year'),
                'modified_user'      => 'ModifiedUser.username',
                'modified'           => $this->aliasField('modified'),
                'created_user'       => 'CreatedUser.username',
                'created'            => $this->aliasField('created'),
                'staff_id'        => $this->aliasField('staff_id'),
                'security_group_user_id'        => $this->aliasField('security_group_user_id'),
            ])
            ->contain([
                'Institutions',
                'Users',
                'StaffStatuses',
                'StaffTypes',
                'StaffPositionGrades',
                'Positions',
                'SecurityGroupUsers.SecurityGroups',
                'ModifiedUser',
                'CreatedUser'
            ])
            ->order(['Institutions.name' => 'ASC']);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $newFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $newFields[] = [
            'key' => 'staff_name',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Staff Name')
        ];
        $newFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS No')
        ];
        $newFields[] = [
            'key' => 'staff_status',
            'field' => 'staff_status',
            'type' => 'string',
            'label' => __('Staff Status')
        ];
        $newFields[] = [
            'key' => 'staff_type',
            'field' => 'staff_type',
            'type' => 'string',
            'label' => __('Staff Type')
        ];
        $newFields[] = [
            'key' => 'staff_position_grade',
            'field' => 'staff_position_grade',
            'type' => 'string',
            'label' => __('Staff Position Grade')
        ];
        $newFields[] = [
            'key' => 'institution_position',
            'field' => 'institution_position',
            'type' => 'string',
            'label' => __('Institution Position')
        ];
        $newFields[] = [
            'key' => 'is_homeroom',
            'field' => 'is_homeroom',
            'type' => 'string',
            'label' => __('Is Homeroom')
        ];
        $newFields[] = [
            'key' => 'fte',
            'field' => 'fte',
            'type' => 'string',
            'label' => __('FTE')
        ];
        $newFields[] = [
            'key' => 'security_role',
            'field' => 'security_role',
            'type' => 'string',
            'label' => __('Security Role')
        ];
        $newFields[] = [
            'key' => 'start_date',
            'field' => 'start_date',
            'type' => 'string',
            'label' => __('Start Date')
        ];
        $newFields[] = [
            'key' => 'start_year',
            'field' => 'start_year',
            'type' => 'string',
            'label' => __('Start Year')
        ];
         $newFields[] = [
            'key' => 'end_date',
            'field' => 'end_date',
            'type' => 'string',
            'label' => __('End Date')
        ];
        $newFields[] = [
            'key' => 'end_year',
            'field' => 'end_year',
            'type' => 'string',
            'label' => __('End Year')
        ];
        $newFields[] = [
            'key' => 'modified_user',
            'field' => 'modified_user',
            'type' => 'string',
            'label' => __('Modified User')
        ];
        $newFields[] = [
            'key' => 'modified',
            'field' => 'modified',
            'type' => 'string',
            'label' => __('Modified')
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
    public function onExcelGetModified(EventInterface $event, Entity $entity) {
        if (!empty($entity->modified)) {
            return $this->formatDate($entity->modified);
        }
    }
    public function onExcelGetCreated(EventInterface $event, Entity $entity) {
        if (!empty($entity->created)) {
            return $this->formatDate($entity->created);
        }
    }

    public function onExcelGetSecurityRole(EventInterface $event, Entity $entity)
    {
        $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $SecurityRoles = TableRegistry::getTableLocator()->get('Security.SecurityRoles');

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
