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

class StaffLeaveTable extends AppTable {
    public function initialize(array $config)
    {
        $this->table('institution_staff_leave');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        $this->addBehavior('Excel');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('AcademicPeriod.Period');
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
        $position = $requestData->position;
        $leaveType = $requestData->leave_type;
        $workflowStatus = $requestData->workflow_status;

        $condition = [];

        if(isset($institutionId) && !empty($institutionId)){
            $conditions[$this->aliasfield('institution_id')] = $institutionId;
        }
        if(isset($position) && !empty($position)){
            $conditions['InstitutionPositions.staff_position_title_id'] = $position;
        }
        if(isset($leaveType) && !empty($leaveType)){
            $conditions[$this->aliasfield('staff_leave_type_id')] = $leaveType;
        }
        if(isset($workflowStatus) && !empty($workflowStatus)){
            $conditions[$this->aliasfield('status_id')] = $workflowStatus;
        }
        if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
            $query->find('academicPeriod', ['academic_period_id' => $academicPeriodId, 'start_date_field' => 'date_from', 'end_date_field' => 'date_to']);
        }

        $query
            ->select(['openemis_no' => 'Users.openemis_no',
					'staff_id' => $this->aliasfield('staff_id'),
                    'code' => 'Institutions.code', 
                    'area_name' => 'Areas.name',
                    'area_code' => 'Areas.code',
                    //'area_administrative_code' => 'AreaAdministratives.code',//POCOR-5762 
                    //'area_administrative_name' => 'AreaAdministratives.name',//POCOR-5762
					'position_title' =>  $query->func()->concat([
						'InstitutionPositions.position_no' => 'literal',
						" - ",
						'StaffPositionTitles.name' => 'literal'
					])
					//'identity_type' => 'IdentityTypes.name',//POCOR-5762
					//'identity_number' => 'UserIdentity.number',//POCOR-5762
            ])
            ->contain(['Users', 'Institutions', 'Institutions.Areas', 'Institutions.AreaAdministratives'])
            ->leftJoin(['InstitutionStaffs' => 'institution_staff'], [
				'InstitutionStaffs.staff_id = ' . $this->aliasfield('staff_id'),
			])
			->leftJoin(['InstitutionPositions' => 'institution_positions'], [
				'InstitutionPositions.id = InstitutionStaffs.institution_position_id',
			])
			->leftJoin(['StaffPositionTitles' => 'staff_position_titles'], [
				'StaffPositionTitles.id = InstitutionPositions.staff_position_title_id',
			]);
            if(!empty($conditions)){
                $query->where($conditions);
            }
			$query->order([$this->aliasField('date_from')]);
            //POCOR-5762 starts
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    $nationalities = TableRegistry::get('nationalities');
                    $identity_types = TableRegistry::get('identity_types');
                    $user_identities = TableRegistry::get('user_identities');
                    $userData = $user_identities->find()
                                    ->select([
                                        $user_identities->aliasfield('identity_type_id'),
                                        $user_identities->aliasfield('number'),
                                        $user_identities->aliasfield('nationality_id'),
                                        $nationalities->aliasfield('id'),
                                        $nationalities->aliasfield('identity_type_id'),
                                        $nationalities->aliasfield('default'),
                                        $identity_types->aliasfield('name')
                                    ])
                                    ->leftJoin(
                                        [$nationalities->alias() => $nationalities->table()],
                                        [
                                            $nationalities->aliasField('id') . ' = '. $user_identities->aliasField('nationality_id')
                                        ]
                                    )
                                    ->leftJoin(
                                        [$identity_types->alias() => $identity_types->table()],
                                        [
                                            $identity_types->aliasField('id') . ' = '. $user_identities->aliasField('identity_type_id')
                                        ]
                                    )
                                    ->where([$user_identities->aliasField('security_user_id') => $row['staff_id']])
                                    ->toArray();

                    $row['identity_type'] = $row['identity_number'] = '';
                    if(!empty($userData)){
                        $with_default_arr = [];
                        $without_default_arr = [];
                        foreach ($userData as $user) {
                            if($user->nationalities['default'] == 1){
                                $with_default_arr['identity_type'] =  $user->identity_types['name'];  
                                $with_default_arr['identity_number'] =  $user->number;  
                            }else{
                                $without_default_arr['identity_type'] =  $user->identity_types['name'];  
                                $without_default_arr['identity_number'] =  $user->number;
                            }
                        }
                    }                

                    if(!empty($with_default_arr)){
                        $row['identity_type'] = $with_default_arr['identity_type'];
                        $row['identity_number'] = $with_default_arr['identity_number'];
                    } else if(empty($with_default_arr) && !empty($without_default_arr)){
                        $row['identity_type'] = $without_default_arr['identity_type'];
                        $row['identity_number'] = $without_default_arr['identity_number'];
                    }
                    return $row;
                });
            });
            //POCOR-5762 ends
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'StaffLeave.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'StaffLeave.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'Institutions.area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Education Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.area',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
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
            $newFields[] = [
                'key' => '',
                'field' => $custom_field_id,
                'type' => 'string',
                'label' => __($custom_field)
            ];
        }
        
        $newFields[] = [
            'key' => 'StaffLeave.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'position_title',
            'type' => 'string',
            'label' => __('Position')
        ];

        $newFields[] = [
            'key' => 'StaffLeave.staff_leave_type_id',
            'field' => 'staff_leave_type_id',
            'type' => 'integer',
            'label' => __('Leave Type')
        ];

        $newFields[] = [
            'key' => 'StaffLeave.date_from',
            'field' => 'date_from',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffLeave.date_to',
            'field' => 'date_to',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffLeave.number_of_days',
            'field' => 'number_of_days',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffLeave.comments',
            'field' => 'comments',
            'type' => 'string',
            'label' => ''
        ];
		
		$fields->exchangeArray($newFields);
    }
}
