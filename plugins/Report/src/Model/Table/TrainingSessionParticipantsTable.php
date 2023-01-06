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
        $Areas = TableRegistry::get('areas'); //POCOR-6594 <vikas.rathore@mail.valuecoders.com>
        $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
        $TrainingSession = TableRegistry::get('Training.TrainingSessions');

        $requestData = json_decode($settings['process']['params']);
        $trainingCourseId = $requestData->training_course_id;
        $trainingSessionId = $requestData->training_session_id;

        $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');

        $academicPeriodId = $requestData->academic_period_id;
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d'); 

        $conditions = [];
        //POCOR-6828 Starts
        if($trainingCourseId != '-1'){
            $conditions['Courses.id'] = $trainingCourseId;
        }

        if (!empty($academicPeriodId)) {
                $conditions['OR'] = [
                    'OR' => [
                        [
                            $TrainingSession->aliasField('end_date') . ' IS NOT NULL',
                            $TrainingSession->aliasField('start_date') . ' <=' => $startDate,
                            $TrainingSession->aliasField('end_date') . ' >=' => $startDate
                        ],
                        [
                            $TrainingSession->aliasField('end_date') . ' IS NOT NULL',
                            $TrainingSession->aliasField('start_date') . ' <=' => $endDate,
                            $TrainingSession->aliasField('end_date') . ' >=' => $endDate
                        ],
                        [
                            $TrainingSession->aliasField('end_date') . ' IS NOT NULL',
                            $TrainingSession->aliasField('start_date') . ' >=' => $startDate,
                            $TrainingSession->aliasField('end_date') . ' <=' => $endDate
                        ]
                    ],
                    [
                        $TrainingSession->aliasField('end_date') . ' IS NULL',
                        $TrainingSession->aliasField('start_date') . ' <=' => $endDate
                    ]
                ];
        }//POCOR-6828 Ends
      
        $query
            ->select([
                $this->aliasField('trainee_id'),
                $this->aliasField('status'),
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'position_name' => 'StaffPositionTitles.name',
                'participant_area' => $Areas->aliasField('name') //POCOR-6594 <vikas.rathore@mail.valuecoders.com>
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
                        //POCOR-6594 starts <vikas.rathore@mail.valuecoders.com> 
                        'trainee_name' => $this->Trainees->find()->func()->concat([
                            'Trainees.first_name' => 'literal',
                            " ",
                            'Trainees.middle_name' => 'literal',
                            " ",
                            'Trainees.third_name' => 'literal',
                            " ",
                            'Trainees.last_name' => 'literal'
                        ]),
                        //POCOR-6594 ends <vikas.rathore@mail.valuecoders.com> 
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
            //POCOR-6594 starts <vikas.rathore@mail.valuecoders.com>
            ->innerJoin(
                [$Areas->alias() => $Areas->table()],
                [
                    $Areas->aliasField('id = ') . $Institutions->aliasField('area_id')
                ]
            )
            ->innerJoin(//POCOR-6828 Starts
                [$TrainingSession->alias() => $TrainingSession->table()],
                [
                    $TrainingSession->aliasField('id = ') . $this->aliasField('training_session_id')
                ]
            )//POCOR-6828 Ends
            //POCOR-6594 end <vikas.rathore@mail.valuecoders.com>
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
            ->where([$conditions])
            ->group([
                $this->aliasField('training_session_id'),
                $this->aliasField('trainee_id')
            ]);

        if (!empty($trainingSessionId) && ($trainingSessionId != -1)) {
            $query->where([$this->aliasField('training_session_id') => $trainingSessionId]);
        }

        // POCOR-6594 get other identities data
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $userIdentities = TableRegistry::get('user_identities');
                $userIdentitiesResult = $userIdentities->find()
                    ->leftJoin(['IdentityTypes' => 'identity_types'], ['IdentityTypes.id = '. $userIdentities->aliasField('identity_type_id')])
                    ->select([
                        'identity_number' => $userIdentities->aliasField('number'),
                        'identity_type_name' => 'IdentityTypes.name',
                    ])
                    ->where([$userIdentities->aliasField('security_user_id') => $row->trainee_id])
                    ->order([$userIdentities->aliasField('id DESC')])
                    ->hydrate(false)->toArray();
                    $row->custom_identity_number = '';
                    $other_identity_array = [];
                    if (!empty($userIdentitiesResult)) {
                        foreach ( $userIdentitiesResult as $index => $user_identities_data ) {
                            if ($index == 0) {
                                $row->custom_identity_number = $user_identities_data['identity_number'];
                                $row->custom_identity_name   = $user_identities_data['identity_type_name'];
                            } else {
                                $other_identity_array[] = '(['.$user_identities_data['identity_type_name'].'] - '.$user_identities_data['identity_number'].')';
                            }
                        }
                    }
                $row->custom_identity_other_data = implode(',', $other_identity_array);
                return $row;
            });
        });
        // POCOR-6594 get other identities data
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
            'label' => __('Participant Name') //POCOR-6594 <vikas.rathore@mail.valuecoders.com>
        ];
        //POCOR-6594 <vikas.rathore@mail.valuecoders.com>
        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'participant_area',
            'type' => 'string',
            'label' => __('Participant Area')
        ];
        //POCOR-6594 <vikas.rathore@mail.valuecoders.com>

        $newFields[] = [
            'key' => 'MainIdentityTypes.name',//POCOR-6594 <vikas.rathore@mail.valuecoders.com> fixxed key name
            'field' => 'identity_type_name',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $newFields[] = [
            'key' => 'Trainees.identity_number',//POCOR-6594 <vikas.rathore@mail.valuecoders.com> fixed key name
            'field' => 'identity_number',
            'type' => 'integer',
            'label' => ''
        ];

        //POCOR-6594 <vikas.rathore@mail.valuecoders.com>
        $newFields[] = [
            'key' => 'custom_identity_other_data',
            'field' => 'custom_identity_other_data',
            'type' => 'string',
            'label' => __('Other Identites')
        ];
        //POCOR-6594 <vikas.rathore@mail.valuecoders.com>

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
