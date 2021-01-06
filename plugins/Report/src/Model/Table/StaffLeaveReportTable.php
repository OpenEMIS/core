<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffLeaveReportTable extends AppTable {
    public function initialize(array $config)
    {
       $this->table('institution_staff_leave');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => ['end_academic_period_id']
        ]);
        
       
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
         $requestData = json_decode($settings['process']['params']);
         $academicPeriodId = $requestData->academic_period_id;
         $institutionId = $requestData->institution_id;
         $staffLeaveTypeId = $requestData->staff_leave_type_id;
         
        $conditions = [];
         
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId)) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }

        if (!empty($staffLeaveTypeId)) {
            $conditions[$this->aliasField('staff_leave_type_id')] = $staffLeaveTypeId;
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
                           $this->aliasfield('institution_id') . ' = '.'Institutions.id'
                        ])
             ->leftJoin(['StaffLeaveTypes' => 'staff_leave_types'], [
                           $this->aliasfield('staff_leave_type_id') . ' = StaffLeaveTypes.id'
                        ])
            ->where($conditions); 
			$query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
				return $results->map(function ($row) {
					
					$StaffCustomFieldValues = TableRegistry::get('staff_custom_field_values');
					
					$customFieldData = $StaffCustomFieldValues->find()
						->select([
							'custom_field_id' => 'StaffCustomFields.id',
							'staff_custom_field_values.text_value',
							'staff_custom_field_values.number_value',
							'staff_custom_field_values.decimal_value',
							'staff_custom_field_values.textarea_value',
							'staff_custom_field_values.date_value'
						])
						->innerJoin(
							['StaffCustomFields' => 'staff_custom_fields'],
							[
								'StaffCustomFields.id = staff_custom_field_values.staff_custom_field_id'
							]
						)
						->where(['staff_custom_field_values.staff_id' => $row['staff_id']])
						->toArray();
					
					foreach($customFieldData as $data) {
						if(!empty($data->text_value)) {
							$row[$data->custom_field_id] = $data->text_value;
						} 
						if(!empty($data->number_value)) {
							$row[$data->custom_field_id] = $data->number_value;
						}
						if(!empty($data->decimal_value)) {
							$row[$data->custom_field_id] = $data->decimal_value;
						}
						if(!empty($data->textarea_value)) {
							$row[$data->custom_field_id] = $data->textarea_value;
						}
						if(!empty($data->date_value)) {
							$row[$data->custom_field_id] = $data->date_value;
							
						}
						
					}
					return $row;
				});
			});
    }

    public function onExcelRenderDateFrom(Event $event, Entity $entity, $attr)
    {
        $date_from = $entity->date_from->format('Y-m-d');
        $entity->date_from = $date_from;
        return $entity->date_from;
    }

    public function onExcelRenderDateTo(Event $event, Entity $entity, $attr)
    {
        $date_to = $entity->date_to->format('Y-m-d');
        $entity->date_to = $date_to;
        return $entity->date_to;
    }

    public function onExcelRenderStartTime(Event $event, Entity $entity, $attr)
    {
        if (!empty($entity->start_time)) {
        $start_time = $entity->start_time->format('h:i:s a');
        $entity->start_time = $start_time;
        }
        return $entity->start_time;
    }

    public function onExcelRenderEndTime(Event $event, Entity $entity, $attr)
    {
        if (!empty($entity->end_time)) {
        $end_time = $entity->end_time->format('h:i:s a');
        $entity->end_time = $end_time;
        }
        return $entity->end_time;
    }
    
    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $identityTypeName = '';
        if (!empty($entity->identity_type)) {
            $identityType = TableRegistry::get('FieldOption.IdentityTypes')->find()->where(['id'=>$entity->identity_type])->first();
            $identityTypeName = $identityType->name;
        }
        return $identityTypeName;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
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
		
		$StaffCustomFields = TableRegistry::get('staff_custom_fields');
					
		$customFieldData = $StaffCustomFields->find()
			->select([
				'custom_field_id' => 'staff_custom_fields.id',
				'custom_field' => 'staff_custom_fields.name'
			])
			->toArray();
		
		foreach($customFieldData as $data) {
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
