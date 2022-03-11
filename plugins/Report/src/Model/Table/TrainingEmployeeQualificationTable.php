<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
/**
 * Get details of all Employee Qualification 
 * POCOR-6598
 * @author divyaa
*/
class TrainingEmployeeQualificationTable extends AppTable
{
    private $trainingSessionResults = [];
    private $institutionDetails = [];

    CONST ACTIVE_STATUS = 1;

    public function initialize(array $config)
    {
        $this->table('institution_staff');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institutions.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('Excel');
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $TrainingSessionResults = TableRegistry::get('Training.TrainingSessionResults');
        $WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
        $WorkflowStatusesSteps = TableRegistry::get('Workflow.WorkflowStatusesSteps');

        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;
		$selectedCourse = $requestData->training_course_id;
		$conditions = [];
        if ($selectedCourse != '-1') {
            $conditions['Courses.id'] = $selectedCourse;
        }

        $query
            ->select([
                'result' => $this->aliasField('result'),
                'attendance_days' => $this->aliasField('attendance_days'),//5695
                'practical' => $this->aliasField('practical'),//5695
                'certificate_number' => $this->aliasField('certificate_number'),//5695
                'workflow_step_name' => $WorkflowSteps->aliasField('name'),
                'openemis_no' => 'Trainees.openemis_no',
                'course_code' => 'Courses.code',
                'course_name' => 'Courses.name',
                'credit_hours' => 'Courses.credit_hours',
                'session_code' => 'Sessions.code',
                'identity_type_name' => 'IdentityTypes.name',
                'identity_number' => 'Trainees.identity_number'
                //'result_type' => 'ResultTypes.name'//5695 
            ])
            ->contain(['Sessions.Courses'])
            /*->innerJoin(
                ['ResultTypes' => 'training_result_types'],
                ['ResultTypes.id = ' . $this->aliasField('training_result_type_id')]
            )*///5695
			->innerJoin(
                [$TrainingSessionResults->alias() => $TrainingSessionResults->table()],
                [$TrainingSessionResults->aliasField('training_session_id = ') . $this->aliasField('training_session_id')]
            )
            ->innerJoin(
                [$WorkflowSteps->alias() => $WorkflowSteps->table()],
                [$WorkflowSteps->aliasField('id = ') . $TrainingSessionResults->aliasField('status_id')]
            )
            ->join([
                'Trainees' => [
                    'type' => 'LEFT',
                    'table' => 'security_user',
                    'conditions' => [
                        'Trainees.id = ' . $this->aliasField('trainee_id')
                    ]
                ],
                'IdentityTypes' => [
                    'type' => 'LEFT',
                    'table' => 'identity_types',
                    'conditions' => [
                        'IdentityTypes.id = ' . $this->Trainees->aliasField('identity_type_id')
                    ]
                ],
            ])
            ->where([$conditions])
            ->group([
                $this->aliasField('training_session_id'),
                $this->aliasField('trainee_id')
            ])
            ->order([$this->aliasField('training_session_id'), $this->aliasField('trainee_id')]);

        if ($selectedStatus != '-1') {
            $query
                ->innerJoin(
                    [$WorkflowStatusesSteps->alias() => $WorkflowStatusesSteps->table()],
                    [$WorkflowStatusesSteps->aliasField('workflow_step_id = ') . $WorkflowSteps->aliasField('id')]
                )
                ->where([$WorkflowStatusesSteps->aliasField('workflow_status_id') => $selectedStatus]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'WorkflowSteps.status',
            'field' => 'workflow_step_name',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'TrainingResults.trainee_id',
            'field' => 'trainee_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'IdentityTypes.name',
            'field' => 'identity_type_name',
            'type' => 'string',
            'label' => __('Identity Type'),
        ];

        $newFields[] = [
            'key' => 'Trainess.identity_number',
            'field' => 'identity_number',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'institution_code',
            'label' => __('Institution Code'),
        ];

        $newFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'institution_name',
            'label' => __('Institution Name'),
        ];

        $newFields[] = [
            'key' => 'Courses.course_code',
            'field' => 'course_code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Courses.course_name',
            'field' => 'course_name',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Sessions.session_code',
            'field' => 'session_code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'TrainingResults.training_session_id',
            'field' => 'training_session_id',
            'type' => 'integer',
            'label' => __('Session Name'),
        ];

        $newFields[] = [
            'key' => 'Courses.credit_hours',
            'field' => 'credit_hours',
            'type' => 'integer',
            'label' => '',
        ];
		
        /*$newFields[] = [
            'key' => 'result_type',
            'field' => 'result_type',
            'type' => 'string',
            'label' => __('Result Type'),
        ];*///5695 starts
		
        $newFields[] = [
            'key' => 'result',
            'field' => 'result',
            'type' => 'string',
            'label' => __('Result'),
        ];
        //5695 starts
        $newFields[] = [
            'key' => 'practical',
            'field' => 'practical',
            'type' => 'string',
            'label' => __('Practical'),
        ];

        $newFields[] = [
            'key' => 'attendance_days',
            'field' => 'attendance_days',
            'type' => 'string',
            'label' => __('Attendance Days'),
        ];

        $newFields[] = [
            'key' => 'certificate_number',
            'field' => 'certificate_number',
            'type' => 'string',
            'label' => __('Certificate Number'),
        ];
        //5695 ends
        $fields->exchangeArray($newFields);
    }

    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $userIdentities = TableRegistry::get('user_identities');
        $userIdentitiesResult = $userIdentities->find()
                ->leftJoin(['IdentityTypes' => 'identity_types'], ['IdentityTypes.id = '. $userIdentities->aliasField('identity_type_id')])
                ->select([
                    'identity_number' => $userIdentities->aliasField('number'),
                    'identity_type_name' => 'IdentityTypes.name',
                ])
                ->where([$userIdentities->aliasField('security_user_id') => $entity->user_id_id]) // POCOR-6597
                ->order([$userIdentities->aliasField('id DESC')])
                ->hydrate(false)->toArray();
                $entity->custom_identity_number = '';
                $other_identity_array = [];
                if (!empty($userIdentitiesResult)) {
                    foreach ( $userIdentitiesResult as $index => $user_identities_data ) {
                        if ($index == 0) {
                            $entity->custom_identity_number = $user_identities_data['identity_number'];
                            $entity->custom_identity_name   = $user_identities_data['identity_type_name'];
                        } else {
                            $other_identity_array[] = '(['.$user_identities_data['identity_type_name'].'] - '.$user_identities_data['identity_number'].')';
                        }
                    }
                }
        $entity->custom_identity_other_data = implode(',', $other_identity_array);
        return $entity->custom_identity_name;
    }

    public function onExcelGetAreaName(Event $event, Entity $entity)
    {
        // if ($entity->has('staff') && !empty($entity->staff)) { // POCOR-6597
            $InstitutionStaff = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $statuses = $StaffStatuses->findCodeList();
            $query = $InstitutionStaff->find('all')
                    ->contain(['Institutions'])
                    ->where([
                        $InstitutionStaff->aliasField('staff_id') => $entity->user_id_id, // POCOR-6597
                        $InstitutionStaff->aliasField('staff_status_id') => $statuses['ASSIGNED']
                    ])
                    ->order([
                        $InstitutionStaff->aliasField('start_date DESC'),
                        $InstitutionStaff->aliasField('created DESC')
                    ])
                    ->first();
            if (!empty($query)) {
                $AreaTable = TableRegistry::get('Area.Areas');
                $value = $AreaTable->find()->where([$AreaTable->aliasField('id') => $query->institution->area_id])->first();
                if (empty($value)) {
                    return ' - ';
                } else {
                    return $value->name;
                }
            }
        // } // POCOR-6597
    }

    

    
}
