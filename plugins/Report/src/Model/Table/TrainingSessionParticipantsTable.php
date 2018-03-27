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
        
        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
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
        $Staff = TableRegistry::get('Institution.Staff');
        $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $Positions = TableRegistry::get('Institution.InstitutionPositions');
        $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');

        $requestData = json_decode($settings['process']['params']);
        $trainingCourseId = $requestData->training_course_id;
        $trainingSessionId = $requestData->training_session_id;

        $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
        
        $query
            ->select([
                $this->aliasField('trainee_id'),
                $this->aliasField('status'),
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'position_name' => 'StaffPositionTitles.name'
            ])
            ->innerJoinWith('Sessions', function ($q) {
                return $q->select([
                        'session_code' => 'Sessions.code',
                        'session_name' => 'Sessions.name',
                        'session_start_date' => 'Sessions.start_date',
                        'session_end_date' => 'Sessions.end_date'
                    ])
                    ->innerJoinWith('Courses', function ($q) {
                        return $q->select([
                            'course_code' => 'Courses.code',
                            'course_name' => 'Courses.name'
                        ]);
                    });
            })
            ->innerJoinWith('Trainees', function ($q) {
                return $q->select([
                        'openemis_no' => 'Trainees.openemis_no',
                        'Trainees.first_name',
                        'Trainees.middle_name',
                        'Trainees.third_name',
                        'Trainees.last_name',
                        'Trainees.preferred_name',
                        'identity_number' => 'Trainees.identity_number'
                    ])
                    ->leftJoinWith('MainIdentityTypes', function ($q) {
                        return $q->select([
                            'identity_type_name' => 'MainIdentityTypes.name'
                        ]);
                    });
            })
            ->leftJoin(
                [$Staff->alias() => $Staff->table()],
                [
                    $Staff->aliasField('staff_id = ') . $this->aliasField('trainee_id'),
                    $Staff->aliasField('staff_status_id') => $assignedStatus
                ]
            )
            ->leftJoin(
                [$Institutions->alias() => $Institutions->table()],
                [
                    $Institutions->aliasField('id = ') . $Staff->aliasField('institution_id')
                ]
            )
            ->leftJoin(
                [$Positions->alias() => $Positions->table()],
                [
                    $Positions->aliasField('id = ') . $Staff->aliasField('institution_position_id')
                ]
            )
            ->leftJoin(
                [$StaffPositionTitles->alias() => $StaffPositionTitles->table()],
                [
                    $StaffPositionTitles->aliasField('id = ') . $Positions->aliasField('staff_position_title_id')
                ]
            )
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
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Courses.course_name',
            'field' => 'course_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Sessions.session_code',
            'field' => 'session_code',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Sessions.session_name',
            'field' => 'session_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Sessions.start_date',
            'field' => 'session_start_date',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Sessions.end_date',
            'field' => 'session_end_date',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'TrainingSessionParticipants.trainee_id',
            'field' => 'trainee_id',
            'type' => 'string',
            'label' => __('Name')
        ];

        $newFields[] = [
            'key' => 'IdentityTypes.name',
            'field' => 'identity_type_name',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $newFields[] = [
            'key' => 'Trainess.identity_number',
            'field' => 'identity_number',
            'type' => 'integer',
            'label' => ''
        ];

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
            'key' => 'position_name',
            'field' => 'position_name',
            'type' => 'string',
            'label' => __('Position')
        ];
        $newFields[] = [
            'key' => 'trainee_status',
            'field' => 'trainee_status',
            'type' => 'trainee_status',
            'label' => __('Status')
        ];

        $fields->exchangeArray($newFields);
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
}
