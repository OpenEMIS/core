<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class TrainingSessionParticipantsTable extends AppTable
{
    private $trainingSessionResults = [];
    private $institutionDetails = [];

    CONST ACTIVE_STATUS = 1;
    CONST WITHDRAWN_STATUS = 2;

    public function initialize(array $config)
    {
        $this->table('training_sessions_trainees');
        parent::initialize($config);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Trainees', ['className' => 'User.Users', 'foreignKey' => 'trainee_id']);
        
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
        $requestData = json_decode($settings['process']['params']);
        $trainingCourseId = $requestData->training_course_id;
        $trainingSessionId = $requestData->training_session_id;

        $query
            ->select([
                'course_code' => 'Courses.code',
                'course_name' => 'Courses.name',
                'session_code' => 'Sessions.code',
                'session_name' => 'Sessions.name',
                'session_start_date' => 'Sessions.start_date',
                'session_end_date' => 'Sessions.end_date',
                'openemis_no' => 'Trainees.openemis_no',
                'identity_type_name' => 'IdentityTypes.name',
                'identity_number' => 'Trainees.identity_number'
            ])
            ->contain(['Sessions.Courses'])
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
                ]
            ])
            ->where(['Courses.id' => $trainingCourseId])
            ->group([
                $this->aliasField('training_session_id'),
                $this->aliasField('trainee_id')
            ]);

        if (!empty($trainingSessionId)) {
            $query->where([$this->aliasField('training_session_id') => $trainingSessionId]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

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
            'key' => 'TrainingSessionParticipants.training_session_id',
            'field' => 'training_session_id',
            'type' => 'integer',
            'label' => __('Session Name'),
        ];

        $newFields[] = [
            'key' => 'Sessions.start_date',
            'field' => 'session_start_date',
            'type' => 'date',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Sessions.end_date',
            'field' => 'session_end_date',
            'type' => 'date',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'TrainingSessionParticipants.trainee_id',
            'field' => 'trainee_id',
            'type' => 'string',
            'label' => __('Name'),
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
            'key' => 'position_name',
            'field' => 'position_name',
            'type' => 'position_name',
            'label' => __('Position'),
        ];
        $newFields[] = [
            'key' => 'trainee_status',
            'field' => 'trainee_status',
            'type' => 'trainee_status',
            'label' => __('Status'),
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelRenderInstitutionCode(Event $event, Entity $entity)
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

    public function onExcelRenderPositionName(Event $event, Entity $entity)
    {
        if (isset($this->institutionDetails) && array_key_exists('institution', $this->institutionDetails)) {
            $InstitutionPositions = TableRegistry::get('Institution.InstitutionPositions');
            $institutionPositionId = $this->institutionDetails['institution_position_id'];
            $staffPositionTitles = $InstitutionPositions->find()
                ->contain(['StaffPositionTitles'])
                ->where([$InstitutionPositions->aliasField('id') => $institutionPositionId])
                ->first();

            return $staffPositionTitles['staff_position_title']['name'];
        } else {
            return ' ';
        }
    }

    public function onExcelRenderTraineeStatus(Event $event, Entity $entity)
    {
        if ($entity->has('status')) {
            $status = $entity->status;
            
            if ($status == self::ACTIVE_STATUS) {
                return 'Active';
            } else if ($status == self::WITHDRAWN_STATUS) {
                return 'Withdrawn';
            } else {
                return ' ';
            }
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
