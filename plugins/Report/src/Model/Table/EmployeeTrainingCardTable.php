<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
/**
* Add New Feature Employe traning Cart Report
* @author Akshay Patodi <akshay.patodi@mail.valuecoders.com>
* @ticket POCOR-6592
* 
*/

class EmployeeTrainingCardTable extends AppTable
{
    private $trainingSessionResults = [];
    private $institutionDetails = [];

    CONST ACTIVE_STATUS = 1;
    CONST WITHDRAWN_STATUS = 2;

    public function initialize(array $config)
    {
        $this->table('training_sessions');
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

    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {   
        $userIdentities = TableRegistry::get('user_identities');
        $userIdentitiesResult = $userIdentities->find()
                ->leftJoin(['IdentityTypes' => 'identity_types'], ['IdentityTypes.id = '. $userIdentities->aliasField('identity_type_id')])
                ->select([
                    'identity_number' => $userIdentities->aliasField('number'),
                    'identity_type_name' => 'IdentityTypes.name',
                ])
                ->where([$userIdentities->aliasField('security_user_id') => $entity->userid])
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
        $staffid =  $requestData->guardian_id;
        $join = [];
        $where = [];
        $join['TrainingSessionsTrainees'] = [
            'type' => 'inner',
            'table' => 'training_sessions_trainees',
            'conditions' => ['TrainingSessionsTrainees.training_session_id = ' . $this->aliasField('id')],
        ];
        $join['Areas'] = [
            'type' => 'inner',
            'table' => 'areas',
            'conditions' => ['Areas.id = ' . $this->aliasField('area_id')],
        ];
        $join['SecurityStaffUsers'] = [
            'type' => 'inner',
            'table' => 'security_users',
            'conditions' => ['SecurityStaffUsers.id = TrainingSessionsTrainees.trainee_id'],
        ];
        $join['Genders'] = [
            'type' => 'inner',
            'table' => 'genders',
            'conditions' => ['Genders.id = SecurityStaffUsers.gender_id'],
        ];
        $join['TrainingCourses'] = [
            'type' => 'inner',
            'table' => 'training_courses',
            'conditions' => ['TrainingCourses.id = ' . $this->aliasField('training_course_id')],
        ];
        $join['trainee_institution'] = [
            'type' => 'left',
            'table' => '( SELECT institutions.name, institution_staff.staff_id FROM institution_staff INNER JOIN institutions ON institutions.id = institution_staff.institution_id AND institution_staff.staff_status_id = 1 )',
            'conditions' => ['trainee_institution.staff_id = TrainingSessionsTrainees.trainee_id'],  
        ];
        $join['TrainingSessionTraineeResults'] = [
            'type' => 'left',
            'table' => 'training_session_trainee_results',
            'conditions' => [
            'TrainingSessionTraineeResults.training_session_id = ' . $this->aliasField('id'),
            'TrainingSessionTraineeResults.trainee_id = TrainingSessionsTrainees.trainee_id'],
        ];
        $join['TrainingResultTypes'] = [
            'type' => 'left',
            'table' => 'training_result_types',
            'conditions' => [
            'TrainingResultTypes.id = TrainingSessionTraineeResults.training_result_type_id'],
        ];
        $join[' '] = [
            'type' => 'left',
            'table' => '( SELECT qualification_specialisations.name qualification_specialisations_name ,staff_qualifications.staff_id ,qualification_titles.name qualification_titles_name FROM staff_qualifications INNER JOIN staff_qualifications_specialisations ON staff_qualifications_specialisations.staff_qualification_id = staff_qualifications.id INNER JOIN qualification_specialisations ON qualification_specialisations.id = staff_qualifications_specialisations.qualification_specialisation_id INNER JOIN qualification_titles ON qualification_titles.id = staff_qualifications.qualification_title_id ) AS StaffQualificationInfo',
            'conditions' => ['StaffQualificationInfo.staff_id = TrainingSessionsTrainees.trainee_id'],   
        ];
        $join['EmployeeId'] = [
            'type' => 'left',
            'table' => 'user_identities',
            'conditions' => [
            'EmployeeId.security_user_id = TrainingSessionsTrainees.trainee_id',
            'EmployeeId.identity_type_id = 179'],

        ];
        $join['NationalId'] = [
            'type' => 'left',
            'table' => 'user_identities',
            'conditions' => [
             'NationalId.security_user_id = TrainingSessionsTrainees.trainee_id',
             'NationalId.identity_type_id = 825'],

        ];
        $where['SecurityStaffUsers.id'] = $staffid;
        $query->join($join);
        $query->select([
                'staff_name' => 'CONCAT(SecurityStaffUsers.first_name, " ", SecurityStaffUsers.last_name)',
                'userid' => 'SecurityStaffUsers.id',
                'area' => 'Areas.name',
                'gender' => 'Genders.name',
                'institution' => 'trainee_institution.name',
                'qualifications_specializations' => 'StaffQualificationInfo.qualification_specialisations_name',
                'training_courses' => 'TrainingCourses.name',
                //'result' => 'TrainingSessionTraineeResults.result'
                'result' => 'CONCAT(TrainingResultTypes.name, "-", TrainingSessionTraineeResults.result)'
        ]);
        $query->where($where); 
       // print_r($query->sql()); die;              
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'staff_name',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Staff Name')
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
            'key' => 'area',
            'field' => 'area',
            'type' => 'string',
            'label' => __('Area')
        ];
        $newFields[] = [
            'key' => 'gender',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];
        $newFields[] = [
            'key' => 'institution',
            'field' => 'institution',
            'type' => 'string',
            'label' => __('Institution')
        ];
        $newFields[] = [
            'key' => 'qualifications_specializations',
            'field' => 'qualifications_specializations',
            'type' => 'string',
            'label' => __('Qualification Specializations')
        ];
        $newFields[] = [
            'key' => 'training_courses',
            'field' => 'training_courses',
            'type' => 'string',
            'label' => __('Training Courses')
        ];
        $newFields[] = [
            'key' => 'result',
            'field' => 'result',
            'type' => 'string',
            'label' => __('Result')
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
