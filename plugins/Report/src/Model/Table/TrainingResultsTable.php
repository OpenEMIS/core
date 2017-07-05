<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class TrainingResultsTable extends AppTable
{
    private $trainingSessionResults = [];
    private $institutionDetails = [];

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
        $selectedStatus = $requestData->status;
        $selectedCourse = $requestData->training_course_id;

        $query
            ->select([
                'workflow_step_name' => 'WorkflowSteps.name',
                'openemis_no' => 'Trainees.openemis_no',
                'course_code' => 'Courses.code',
                'course_name' => 'Courses.name',
                'credit_hours' => 'Courses.credit_hours',
                'session_code' => 'Sessions.code',
                'identity_type_name' => 'IdentityTypes.name',
                'identity_number' => 'Trainees.identity_number'
            ])
            ->contain(['Sessions.Courses'])
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
            ->where(['Courses.id' => $selectedCourse])
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

        $requestData = json_decode($settings['process']['params']);
        $selectedCourse = $requestData->training_course_id;

        $TrainingCourses = TableRegistry::get('Training.TrainingCourses');
        $course = $TrainingCourses->get($selectedCourse, ['contain' => ['ResultTypes']]);
        $resultTypes = $course->result_types;

        foreach ($resultTypes as $type) {
            $newFields[] = [
                'key' => 'result',
                'field' => 'result',
                'type' => 'result',
                'label' => __($type->name),
                'resultTypeId' => $type->id
            ];
        }

        $fields->exchangeArray($newFields);
    }

    public function onExcelRenderResult(Event $event, Entity $entity, array $attr)
    {
        $sessionId = $entity->training_session_id;
        $traineeId = $entity->trainee_id;
        $resultTypeId = $attr['resultTypeId'];

        $trainingSessionResults = $this->trainingSessionResults;
        if (!isset($trainingSessionResults[$sessionId][$traineeId][$resultTypeId])) {
            $TrainingResultsTable = TableRegistry::get('Training.TrainingSessionTraineeResults');
            $this->trainingSessionResults = $TrainingResultsTable->getTrainingSessionResults($sessionId);
            $trainingSessionResults = $this->trainingSessionResults;
        }

        $result = $trainingSessionResults[$sessionId][$traineeId][$resultTypeId];
        return $result;
    }

    public function onExcelRenderInstitutionCode(Event $event, Entity $entity, array $attr)
    {
        if ($entity->has('trainee_id')) {
            $traineeId = $entity->trainee_id;
            $this->institutionDetails = $this->getInstitutionDetailByTraineeId($traineeId);

            if (isset($this->institutionDetails) && array_key_exists('institution', $this->institutionDetails)) {
                return $this->institutionDetails['institution']['code'];
            } else {
                return ' ';
            }
        } else {
            return ' ';
        }
    }

    public function onExcelRenderInstitutionName(Event $event, Entity $entity)
    {
        if (isset($this->institutionDetails) && array_key_exists('institution', $this->institutionDetails)) {
            return $this->institutionDetails['institution']['name'];
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

        return $institutionDetails->toArray();
    }
}
