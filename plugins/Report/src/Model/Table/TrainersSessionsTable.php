<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

/**
 * Generate the "Trainers Sessions" Report
 * Page: Reports > Trainings > Trainers Sessions (Feature Drop-Down)
 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
 * @ticket POCOR-6569
 */
class TrainersSessionsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('training_sessions');
        parent::initialize($config);
        $this->addBehavior('Excel', ['excludes' => []]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $trainer_id  = $requestData->trainer_name;
        $start_date  = $requestData->start_date;
        $end_date    = $requestData->end_date;
        $course_id   = $requestData->training_course_id;
        $where       = [];

        if ($trainer_id > 0) {
            $where['TrainingSessionTrainers.trainer_id'] = $trainer_id;//POCOR-6827
        }
        if ($course_id > 0) {
            $where['TrainingCourses.id'] = $course_id;
        }
        if (!empty($start_date)) {
            $where[$this->aliasField('start_date >=')] = date('Y-m-d', strtotime($start_date));
        }
        if (!empty($end_date)) {
            $where[$this->aliasField('end_date <=')] = date('Y-m-d', strtotime($end_date));
        }
        /*if ($course_id > 0) { // POCOR-6827
            $where[$this->aliasField('id')] = $course_id;
        }*/

        $selectable['area']                          = 'Areas.name';
        $selectable['gender']                        = 'Genders.name';
        $selectable['trainer_id']                    = 'TrainingSessionTrainers.trainer_id';
        $selectable['trainer_name']                  = 'TrainingSessionTrainers.name';
        $selectable['training_courses']              = 'TrainingCourses.name';
        $selectable['training_sessions_name']        = $this->aliasField('name');
        $selectable['staff_qualification_info_name'] = 'staff_qualification_info.name';

        $join = [
            'TrainingSessionTrainers' => [
                'type' => 'inner',
                'table' => 'training_session_trainers',
                'conditions' => [
                    'TrainingSessionTrainers.training_session_id = ' . $this->aliasField('id'),
                ]
            ],
            'Areas' => [
                'type' => 'inner',
                'table' => 'areas',
                'conditions' => [
                    'Areas.id = ' . $this->aliasField('area_id'),
                ]
            ],
            'Users' => [
                'type' => 'inner',
                'table' => 'security_users',
                'conditions' => [
                    'Users.id = TrainingSessionTrainers.trainer_id',
                ]
            ],
            ' ' => [
                'type' => 'left',
                'table' => '(SELECT qualification_specialisations.name,staff_qualifications.staff_id FROM staff_qualifications 
                INNER JOIN staff_qualifications_specialisations ON staff_qualifications_specialisations.staff_qualification_id = staff_qualifications.id
                INNER JOIN qualification_specialisations ON qualification_specialisations.id =staff_qualifications_specialisations.qualification_specialisation_id) AS staff_qualification_info',
                'conditions' => ['staff_qualification_info.staff_id = TrainingSessionTrainers.trainer_id']
            ],
            'Genders' => [
                'type' => 'inner',
                'table' => 'genders',
                'conditions' => [
                    'Genders.id = Users.gender_id',
                ]
            ],
            'TrainingCourses' => [
                'type' => 'inner',
                'table' => 'training_courses',
                'conditions' => [
                    'TrainingCourses.id = ' . $this->aliasField('training_course_id'),
                ]
            ],
        ];

        $query->join($join);
        $query->select($selectable);
        $query->where($where);
       // print_r($query->Sql());die('hj');
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'trainer_name',
            'field' => 'trainer_name',
            'type' => 'text',
            'label' => 'Trainer'
        ];
        $newFields[] = [
            'key' => 'area',
            'field' => 'area',
            'type' => 'text',
            'label' => 'Area'
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
            'key' => 'gender',
            'field' => 'gender',
            'type' => 'text',
            'label' => __('Gender')
        ];
        $newFields[] = [
            'key' => 'staff_qualification_info_name',
            'field' => 'staff_qualification_info_name',
            'type' => 'text',
            'label' => __('Staff Qualification Name')
        ];
        $newFields[] = [
            'key' => 'training_courses',
            'field' => 'training_courses',
            'type' => 'text',
            'label' => __('Training Course')
        ];
        $newFields[] = [
            'key' => 'training_sessions_name',
            'field' => 'training_sessions_name',
            'type' => 'text',
            'label' => __('Training Sessions')
        ];
        $fields->exchangeArray($newFields);
    }
}
