<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class TrainingTrainersTable extends AppTable
{
    private $trainingSessionResults = [];

    public function initialize(array $config)
    {
        $this->table('training_session_trainers');
        parent::initialize($config);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Trainers', ['className' => 'User.Users', 'foreignKey' => 'trainer_id']);

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
        $requestData = json_decode($settings['process']['params']);
        $trainingCourseId = $requestData->training_course_id;
        $trainingSessionId = $requestData->training_session_id;

        $query
            ->select([
                'session_code' => 'Sessions.code',
                'session_name' => 'Sessions.name',
                'session_start_date' => 'Sessions.start_date',
                'session_end_date' => 'Sessions.end_date',
                'openemis_no' => 'Trainers.openemis_no',
                'identity_type_name' => 'IdentityTypes.name',
                'identity_number' => 'Trainers.identity_number'
            ])
            ->matching('Sessions.Courses')
            ->join([
                'Trainers' => [
                    'type' => 'LEFT',
                    'table' => 'security_users',
                    'conditions' => [
                        'Trainers.id = ' . $this->aliasField('trainer_id')
                    ]
                ],
                'IdentityTypes' => [
                    'type' => 'LEFT',
                    'table' => 'identity_types',
                    'conditions' => [
                        'IdentityTypes.id = ' . $this->Trainers->aliasField('identity_type_id')
                    ]
                ],
            ])
            ->where(['Courses.id' => $trainingCourseId])
            ->order([$this->aliasField('name')]);

        if (!empty($trainingSessionId)) {
            $query->where([$this->aliasField('training_session_id') => $trainingSessionId]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Sessions.code',
            'field' => 'session_code',
            'type' => 'string',
            'label' => __('Session Code'),
        ];

        $newFields[] = [
            'key' => 'Sessions.name',
            'field' => 'session_name',
            'type' => 'string',
            'label' => __('Session name'),
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
            'key' => 'name',
            'field' => 'name',
            'type' => 'name',
            'label' => __('Name'),
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelRenderName(Event $event, Entity $entity, array $attr)
    {
        if ($entity->has('trainer_id')) {
            $trainerId = $entity->trainer_id;

            return $this->Trainers->get($trainerId)->name;
        } else {
            return ' ';
        }
    }
}
