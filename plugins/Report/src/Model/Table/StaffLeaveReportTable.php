<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffLeaveReportTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_staff_leave');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => ['end_academic_period_id']
        ]);
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->getAlias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $staffLeaveTypeId = $requestData->staff_leave_type_id;
        $areaId = $requestData->area_education_id;
        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }
        if (!empty($staffLeaveTypeId)) {
            $conditions[$this->aliasField('staff_leave_type_id')] = $staffLeaveTypeId;
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }
        $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'status' => 'WorkFlowSteps.name',
                'assignee' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                ]),
                'staff_id' => 'Staffs.id',
                'openemis_number' => 'Staffs.openemis_no',
                'staff_name' =>  $query->func()->concat([
                    'Staffs.first_name' => 'literal',
                    " ",
                    'Staffs.last_name' => 'literal'
                ]),
                'staff_leave_type' => 'StaffLeaveTypes.name',
                'date_from' =>  $this->aliasfield('date_from'),
                'date_to' =>  $this->aliasfield('date_to'),
                'start_time' =>  $this->aliasfield('start_time'),
                'end_time' =>  $this->aliasfield('end_time'),
                'full_day' =>  $this->aliasfield('full_day'),
                'Number_of_days' =>  $this->aliasfield('number_of_days'),
                'comments' =>  $this->aliasfield('comments'),
                'identity_number' => 'Users.identity_number',
                'identity_type' => 'Users.identity_type_id',
                'academic_period' => 'AcademicPeriods.name'
            ])
            ->innerJoin(['Users' => 'security_users'], [
                'Users.id = ' . $this->aliasfield('assignee_id'),
            ])
            ->innerJoin(['Staffs' => 'security_users'], [
                'Staffs.id = ' . $this->aliasfield('staff_id'),
            ])

            ->leftJoin(['WorkFlowSteps' => 'workflow_steps'], [
                $this->aliasfield('status_id') . ' = WorkFlowSteps.id'
            ])
            ->leftJoin(['AcademicPeriods' => 'academic_periods'], [
                $this->aliasfield('academic_period_id') . ' = AcademicPeriods.id'
            ])
            ->leftJoin(['Institutions' => 'institutions'], [
                $this->aliasfield('institution_id') . ' = ' . 'Institutions.id'
            ])
            ->leftJoin(['StaffLeaveTypes' => 'staff_leave_types'], [
                $this->aliasfield('staff_leave_type_id') . ' = StaffLeaveTypes.id'
            ])
            ->where($conditions);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {

                $StaffCustomFieldValues = TableRegistry::getTableLocator()->get('StaffCustomField.StaffCustomFieldValues');

                // POCOR-9314 start
                $customFieldData = $StaffCustomFieldValues->find()
                    ->select([
                        'custom_field_id' => 'StaffCustomFields.id',
                        'StaffCustomFieldValues.text_value',
                        'StaffCustomFieldValues.number_value',
                        'StaffCustomFieldValues.decimal_value',
                        'StaffCustomFieldValues.textarea_value',
                        'StaffCustomFieldValues.date_value'
                    ])
                    ->from(['StaffCustomFieldValues' => 'staff_custom_field_values'])
                    ->innerJoin(
                        ['StaffCustomFields' => 'staff_custom_fields'],
                        [
                            'StaffCustomFields.id = StaffCustomFieldValues.staff_custom_field_id'
                        ]
                    )
                    ->where(['StaffCustomFieldValues.staff_id' => $row['staff_id']])
                    ->toArray();

                //POCOR-9314 end
                foreach ($customFieldData as $data) {
                    if (!empty($data->text_value)) {
                        $row[$data->custom_field_id] = $data->text_value;
                    }
                    if (!empty($data->number_value)) {
                        $row[$data->custom_field_id] = $data->number_value;
                    }
                    if (!empty($data->decimal_value)) {
                        $row[$data->custom_field_id] = $data->decimal_value;
                    }
                    if (!empty($data->textarea_value)) {
                        $row[$data->custom_field_id] = $data->textarea_value;
                    }
                    if (!empty($data->date_value)) {
                        $row[$data->custom_field_id] = $data->date_value;
                    }
                }
                return $row;
            });
        });
    }

    //  POCOR-9314 start
    private function formatDateCell($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_string($value) && $value !== '') {
            $ts = strtotime($value);
            return $ts ? date('Y-m-d', $ts) : '';
        }
        return '';
    }

    public function onExcelRenderDateFrom(EventInterface $event, Entity $entity, $attr)
    {
        return $this->formatDateCell($entity->get('date_from'));
    }

    public function onExcelRenderDateTo(EventInterface $event, Entity $entity, $attr)
    {
        return $this->formatDateCell($entity->get('date_to'));
    }

    public function onExcelRenderStartTime(EventInterface $e, Entity $entity, $attr)
    {
        $time = $entity->get('start_time');
        if ($time instanceof \DateTimeInterface) return $time->format('h:i:s a');
        if (is_string($time) && $time !== '')     return date('h:i:s a', strtotime($time));
        return '';
    }

    public function onExcelRenderEndTime(EventInterface $e, Entity $entity, $attr)
    {
        $time = $entity->get('end_time');
        if ($time instanceof \DateTimeInterface) return $time->format('h:i:s a');
        if (is_string($time) && $time !== '')       return date('h:i:s a', strtotime($time));
        return '';
    }
    // POCOR-9314 end

    public function onExcelGetIdentityType(EventInterface $event, Entity $entity)
    {
        $identityTypeName = '';
        if (!empty($entity->identity_type)) {
            $identityType = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes')->find()->where(['id' => $entity->identity_type])->first();
            $identityTypeName = $identityType->name;
        }
        return $identityTypeName;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $extraFields[] = [
            'key' => '',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $extraFields[] = [
            'key' => 'Staffs.openemis_no',
            'field' => 'openemis_number',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];


        $extraFields[] = [
            'key' => '',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Staff Name')
        ];

        $extraFields[] = [
            'key' => 'identity_type',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];


        $extraFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];


        $extraFields[] = [
            'key' => 'StaffLeaveTypes.name',
            'field' => 'staff_leave_type',
            'type' => 'string',
            'label' => __('Staff leave Type')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'date_from',
            'type' => 'date_from',
            'label' => __('Date From')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'date_to',
            'type' => 'date_to',
            'label' => __('Date To')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'Number_of_days',
            'type' => 'integer',
            'label' => __('Number of days')
        ];



        $extraFields[] = [
            'key' => '',
            'field' => 'full_day',
            'type' => 'string',
            'label' => __('Full Time')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'start_time',
            'type' => 'start_time',
            'label' => __('Start Time')
        ];



        $extraFields[] = [
            'key' => '',
            'field' => 'end_time',
            'type' => 'end_time',
            'label' => __('End Time')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'assignee',
            'type' => 'string',
            'label' => __('Assignee')
        ];


        $extraFields[] = [
            'key' => 'WorkflowSteps.name',
            'field' => 'status',
            'type' => 'string',
            'label' => __('Status')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'comments',
            'type' => 'string',
            'label' => __('Comments')
        ];

        $StaffCustomFields = TableRegistry::getTableLocator()->get('staff_custom_fields');

        $customFieldData = $StaffCustomFields->find()
            ->select([
                'custom_field_id' => 'staff_custom_fields.id',
                'custom_field' => 'staff_custom_fields.name'
            ])
            ->toArray();

        foreach ($customFieldData as $data) {
            $custom_field_id = $data->custom_field_id;
            $custom_field = $data->custom_field;
            $extraFields[] = [
                'key' => '',
                'field' => $custom_field_id,
                'type' => 'string',
                'label' => __($custom_field)
            ];
        }

        $newFields = $extraFields;

        $fields->exchangeArray($newFields);
    }
}
