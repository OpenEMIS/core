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
            'pages' => false
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
      
        $query
            ->select([
                'status' => 'WorkflowSteps.name',
                'assignee' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                    ]),
                'openemis_number' => 'Users.openemis_no',
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
                'academic_period' => 'AcademicPeriods.name',
                
                
             ])
            ->leftJoin(['Users' => 'security_users'], [
                            'Users.id = ' . $this->aliasfield('assignee_id'),
                        ])
            ->leftJoin(['Staffs' => 'security_users'], [
                            'Staffs.id = ' . $this->aliasfield('staff_id'),
                        ])
           
            ->leftJoin(['WorkFlowSteps' => 'workflow_steps'], [
                            $this->aliasfield('status_id') . ' = WorkFlowSteps.id'
                        ])
            ->leftJoin(['AcademicPeriods' => 'academic_periods'], [
                           $this->aliasfield('academic_period_id') . ' = AcademicPeriods.id'
                        ])
             ->leftJoin(['StaffLeaveTypes' => 'staff_leave_types'], [
                           $this->aliasfield('staff_leave_type_id') . ' = StaffLeaveTypes.id'
                        ])
             ->where(['AcademicPeriods.id='. $academicPeriodId, $this->aliasfield('institution_id'). '='. $institutionId,$this->aliasfield('staff_leave_type_id'). '='. $staffLeaveTypeId]);

 echo $query;
          
          
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
      $cloneFields = $fields->getArrayCopy();

        $extraFields[] = [
            'key' => '',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];  
        
         $extraFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_number',
            'type' => 'string',
            'label' => __('openEMIS ID')
        ];  


         $extraFields[] = [
            'key' => '',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Staff Name')
        ];  

         $extraFields[] = [
            'key' => 'Users.identity_type_id',
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
            'type' => 'string',
            'label' => __('Date From')
        ];

         $extraFields[] = [
            'key' => '',
            'field' => 'date_to',
            'type' => 'string',
            'label' => __('Date To')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'Number_of_days',
            'type' => 'string',
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
            'type' => 'string',
            'label' => __('Start Time')
        ];  
        


         $extraFields[] = [
            'key' => '',
            'field' => 'end_time',
            'type' => 'string',
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
        
        

        
        $newFields = $extraFields;
        
        $fields->exchangeArray($newFields);
    }
}
