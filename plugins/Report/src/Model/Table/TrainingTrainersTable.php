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
            'label' => __('Other Identites')
        ];

        $newFields[] = [
            'key' => 'name',
            'field' => 'name',
            'type' => 'name',
            'label' => __('Name'),
        ];

        $newFields[] = [
            'key' => 'area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => 'Area'
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
   
    // start POCOR-6595
    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $userIdentities = TableRegistry::get('user_identities');
        $userIdentitiesResult = $userIdentities->find()
                ->leftJoin(['IdentityTypes' => 'identity_types'], ['IdentityTypes.id = '. $userIdentities->aliasField('identity_type_id')])
                ->select([
                    'identity_number' => $userIdentities->aliasField('number'),
                    'identity_type_name' => 'IdentityTypes.name',
                ])
                ->where([$userIdentities->aliasField('security_user_id') => $entity->trainer_id])
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

    public function onExcelGetIdentityNumber(Event $event, Entity $entity)
    {
        return $entity->custom_identity_number;
    }

    public function onExcelGetOtherIdentity(Event $event, Entity $entity)
    {
        return $entity->custom_identity_other_data;
    }

    public function onExcelGetAreaName(Event $event, Entity $entity)
    {
        if (!empty($entity->trainer_id)) {
            $InstitutionStaff = TableRegistry::get('Institution.Staff');
            $Institution = TableRegistry::get('Institution.Institutions');
            $AreaTable = TableRegistry::get('Area.Areas');

            $data = $InstitutionStaff->find()
                    ->select([
                        'area_name' => $AreaTable->aliasField('name')
                    ])
                    ->leftjoin(
                        [$Institution->alias() => $Institution->table()],
                        [$Institution->aliasField('id = ').$InstitutionStaff->aliasField('institution_id')]
                    )
                    ->leftjoin(
                        [$AreaTable->alias() => $AreaTable->table()],
                        [$AreaTable->aliasField('id = ').$Institution->aliasField('area_id')]
                    )
                    ->where([
                        $InstitutionStaff->aliasField('staff_id') => $entity->trainer_id,
                        $InstitutionStaff->aliasField('staff_status_id') => 1
                    ])->first();

                if (!empty($data)) {
                    return $data->area_name;                    
                } else {
                    return ' - ';
                }            
        }
    }
    // END POCOR-6595
}
