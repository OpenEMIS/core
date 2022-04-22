<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface; // POCOR-6596

class TrainingResultsTable extends AppTable
{
    private $trainingSessionResults = [];
    private $institutionDetails = [];
    private $_dynamicFieldName = 'custom_field_data'; // POCOR-6596

    CONST ACTIVE_STATUS = 1;

    public function initialize(array $config)
    {
        $this->table('training_session_trainee_results');
        parent::initialize($config);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Trainees', ['className' => 'User.Users', 'foreignKey' => 'trainee_id']);
        $this->belongsTo('TrainingResultTypes', ['className' => 'Training.TrainingResultTypes']);

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
        $start_date   = $requestData->start_date; // POCOR-6596
        $session_name = $requestData->session_name; // POCOR-6596
        $end_date     = $requestData->end_date; // POCOR-6596
        $selectedStatus = $requestData->status;
		$selectedCourse = $requestData->training_course_id;
		$conditions = [];
        if ($selectedCourse > 0) { // POCOR-6596
            $conditions['Courses.id'] = $selectedCourse;
        }
        // START: POCOR-6596
        if ($session_name > 0) {
            $conditions['Sessions.id'] = $session_name;
        }
        if (!empty($start_date)) {

           
            $conditions['Sessions.start_date >= '] = date('Y-m-d', strtotime($start_date));
        }
        if (!empty($end_date)) {
            
            $conditions['Sessions.end_date <= '] = date('Y-m-d', strtotime($end_date));
        }
        // END: POCOR-6596

        $query
            ->select([
                'custom_training_result_type_id' => $this->aliasField('training_result_type_id'), // POCOR-6596
                'result' => $this->aliasField('result'),
                //'attendance_days' => $this->aliasField('attendance_days'), // POCOR-6596
                //'practical' => $this->aliasField('practical'), // POCOR-6596
                //'certificate_number' => $this->aliasField('certificate_number'), // POCOR-6596
                'workflow_step_name' => $WorkflowSteps->aliasField('name'),
                //'openemis_no' => 'Trainees.openemis_no', // POCOR-6596
                'course_code' => 'Courses.code',
                'course_name' => 'Courses.name',
                'credit_hours' => 'Courses.credit_hours',
                'session_code' => 'Sessions.code',
                'identity_type_name' => 'IdentityTypes.name',
                // START : POCOR-6596
                'identity_number' => 'Trainees.identity_number',
                'trainee_info_area' => 'trainee_info.areaname',
                'trainee_info_openemis_no' => 'trainee_info.openemis_no',
                'trainee_info_first_name' => 'trainee_info.first_name',
                'trainee_info_middle_name' => 'trainee_info.middle_name',
                'trainee_info_third_name' => 'trainee_info.third_name',
                'trainee_info_last_name' => 'trainee_info.last_name',
                'trainee_info_gender' => 'trainee_info.gender',
                'trainee_info_institution_code' => 'trainee_info.institution_code',
                'trainee_info_institution_name' => 'trainee_info.institution_name',
                'staff_qualification_info_name' => 'staff_qualification_info.name'
                // END : POCOR-6596
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
                    'table' => 'security_users', // POCOR-6596
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
            // START : POCOR-6596
            ->join([
                'trainee_info' => [
                    'type' => 'inner',
                    'table' => '(SELECT security_users.openemis_no, security_users.first_name, security_users.middle_name, security_users.third_name, security_users.last_name, areas.name AS areaname, areas.id AS area_id, institution_staff.staff_id, institutions.code AS institution_code, institutions.name AS institution_name, staff_position_titles.name AS position_name, staff_statuses.name AS status_name, genders.name AS gender FROM institution_staff INNER JOIN security_users ON security_users.id = institution_staff.staff_id INNER JOIN institutions ON institutions.id = institution_staff.institution_id INNER JOIN areas ON areas.id = institutions.area_id INNER JOIN staff_statuses ON staff_statuses.id = institution_staff.staff_status_id AND staff_statuses.id = 1 INNER JOIN institution_positions ON institution_positions.id = institution_staff.institution_position_id INNER JOIN staff_position_titles ON institution_positions.staff_position_title_id = staff_position_titles.id INNER JOIN genders ON genders.id = security_users.gender_id GROUP BY security_users.id ) ',
                    'conditions' => [
                        'trainee_info.staff_id = ' . $this->aliasField('trainee_id')
                    ]
                ],
            ])
            ->join([
                'staff_qualification_info' => [
                    'type' => 'left',
                    'table' => '( SELECT qualification_specialisations.name, staff_qualifications.staff_id FROM staff_qualifications INNER JOIN staff_qualifications_specialisations ON staff_qualifications_specialisations.staff_qualification_id = staff_qualifications.id INNER JOIN qualification_specialisations ON qualification_specialisations.id = staff_qualifications_specialisations.qualification_specialisation_id )',
                    'conditions' => [
                        'staff_qualification_info.staff_id = ' . $this->aliasField('trainee_id')
                    ]
                ],
            ])
            // END : POCOR-6596
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
        // START : POCOR-6596
        $query->formatResults(function (ResultSetInterface $results) {
            return $results->map(function ($row) {
                $row[$this->_dynamicFieldName.'_'.$row['custom_training_result_type_id']] = $row['result'];
                return $row;
            });
        });
        // END : POCOR-6596
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

        // START : POCOR-6596
        $newFields[] = [
            'key' => 'trainee_info_area',
            'field' => 'trainee_info_area',
            'type' => 'string',
            'label' => 'Area Education',
        ];
        $newFields[] = [
            'key' => 'trainee_info_openemis_no',
            'field' => 'trainee_info_openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID',
        ];
        $newFields[] = [
            'key' => 'trainee_info_trainee_name',
            'field' => 'trainee_info_trainee_name',
            'type' => 'string',
            'label' => __('Trainee')
        ];
        $newFields[] = [
            'key' => 'identity_type',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];
        $newFields[] = [
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $newFields[] = [
            'key' => 'other_identity',
            'field' => 'other_identity',
            'type' => 'string',
            'label' => __('Other Identities')
        ];
        $newFields[] = [
            'key' => 'trainee_info_gender',
            'field' => 'trainee_info_gender',
            'type' => 'string',
            'label' => __('Gender')
        ];
        $newFields[] = [
            'key' => 'trainee_info_institution_code',
            'field' => 'trainee_info_institution_code',
            'type' => 'string',
            'label' => __('Institution Code'),
        ];
        $newFields[] = [
            'key' => 'trainee_info_institution_name',
            'field' => 'trainee_info_institution_name',
            'type' => 'string',
            'label' => __('Institution Name'),
        ];
        $newFields[] = [
            'key' => 'staff_qualification_info_name',
            'field' => 'staff_qualification_info_name',
            'type' => 'string',
            'label' => __('Staff Qualification'),
        ];
        // END : POCOR-6596

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

        /**
         * Get all dynamic column from the table TrainingResultTypes
         * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
         * Ticket: POCOR-6596 START
         */
        $training_result_types = TableRegistry::get('Training.TrainingResultTypes');
        $customFieldData = $training_result_types->find()->select(['id','name','order'])->toArray();
        if(!empty($customFieldData)) {
            foreach($customFieldData as $data) {
                $custom_field_id = $data->id;
                $custom_field = $data->name;
                $newFields[] = [
                    'key' => $this->_dynamicFieldName.'_'.$custom_field_id,
                    'field' => $this->_dynamicFieldName.'_'.$custom_field_id,
                    'type' => 'string',
                    'label' => __('Result Type ' . $custom_field)
                ];
            }
        }
        /** END POCOR-6596 */
        $fields->exchangeArray($newFields);
    }

    public function onExcelRenderInstitutionCode(Event $event, Entity $entity, array $attr)
    {
        if ($entity->has('trainee_id')) {
            $traineeId = $entity->trainee_id;
            $this->institutionDetails = $this->getInstitutionDetailByTraineeId($traineeId);

            if (isset($this->institutionDetails->institution->code) && !empty($this->institutionDetails->institution->code)) {
                return $this->institutionDetails->institution->code;
            } else {
                return ' ';
            }
        } else {
            return ' ';
        }
    }

    public function onExcelRenderInstitutionName(Event $event, Entity $entity)
    {
        if (isset($this->institutionDetails->institution->name) && !empty($this->institutionDetails->institution->name)) {
            return $this->institutionDetails->institution->name;
        } else {
            return ' ';
        }
    }

    public function getInstitutionDetailByTraineeId($traineeId)
    {
        $InstitutionStaff = TableRegistry::get('Institution.Staff');

        $institutionDetails = [];
        $institutionDetails = $InstitutionStaff->find()
            ->contain('Institutions')
            ->where([
                $InstitutionStaff->aliasField('staff_id') => $traineeId,
                $InstitutionStaff->aliasField('staff_status_id') => self::ACTIVE_STATUS
            ])
            ->order([
                $InstitutionStaff->aliasField('start_date') => 'DESC',
            ])
            ->first()
        ;
		
        return $institutionDetails;
    }
 
    /**
     * Concat the user name
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6596
     */
    public function onExcelGetTraineeInfoTraineeName(Event $event, Entity $entity)
    {
        return 
            $entity->trainee_info_first_name  . ' ' .
            $entity->trainee_info_middle_name . ' ' .
            $entity->trainee_info_third_name  . ' ' .
            $entity->trainee_info_last_name;
    }

    /**
     * Generate the user identities
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6596
     */
    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $userIdentities = TableRegistry::get('user_identities');
        $userIdentitiesResult = $userIdentities->find()
            ->leftJoin(['IdentityTypes' => 'identity_types'], ['IdentityTypes.id = '. $userIdentities->aliasField('identity_type_id')])
            ->select([
                'identity_number' => $userIdentities->aliasField('number'),
                'identity_type_name' => 'IdentityTypes.name',
            ])
            ->where([$userIdentities->aliasField('security_user_id') => $entity->trainee_id])
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

    /**
     * Generate the user identity number
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6596
     */
    public function onExcelGetIdentityNumber(Event $event, Entity $entity)
    {
        return $entity->custom_identity_number;
    }

    /**
     * Generate the user other identities
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6596
     */
    public function onExcelGetOtherIdentity(Event $event, Entity $entity)
    {
        return $entity->custom_identity_other_data;
    }
}
