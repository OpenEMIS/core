<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class TrainingNeedsTable extends AppTable  
{
    public function initialize(array $config)
    {
        $this->table('staff_training_needs');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('TrainingCourses', ['className' => 'Training.TrainingCourses']);
        $this->belongsTo('TrainingNeedCategories', ['className' => 'Training.TrainingNeedCategories', 'foreignKey' => 'training_need_category_id']);
        $this->belongsTo('TrainingPriorities', ['className' => 'Training.TrainingPriorities', 'foreignKey' => 'training_priority_id']);
        $this->belongsTo('TrainingNeedCompetencies', ['className' => 'Training.TrainingNeedCompetencies', 'foreignKey' => 'training_need_competency_id']);
        $this->belongsTo('TrainingNeedSubStandards', ['className' => 'Training.TrainingNeedSubStandards', 'foreignKey' => 'training_need_sub_standard_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        $this->addBehavior('Excel', [
            'excludes' => ['assignee_id', 'status_id']
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
        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;
        $selectedNeedType = $requestData->training_need_type;

        $query->contain(['TrainingNeedSubStandards.TrainingNeedStandards', 'TrainingCourses.TrainingRequirements']);

        if ($selectedStatus != '-1') {
            $query->matching('Statuses.WorkflowStatuses', function ($q) use ($selectedStatus) {
                return $q->where(['WorkflowStatuses.id' => $selectedStatus]);
            });
        }

        $query->select([
            'user_id_id' => $this->aliasField('staff_id'), //POCOR-6597
            'course_code' => 'TrainingCourses.code',
            'course_description' => 'TrainingCourses.description',
            'course_requirement' => 'TrainingRequirements.name',
            'training_standard' => 'TrainingNeedStandards.name'
        ]);

        $query->where([$this->aliasField('type') => $selectedNeedType]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $selectedNeedType = $requestData->training_need_type;

        $newFields = [];

        $newFields[] = [
            'key' => 'area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => 'Area'
        ];
        $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => ''
        ];

        if ($selectedNeedType == 'CATALOGUE') {

            $newFields[] = [
                'key' => 'TrainingCourses.course_code',
                'field' => 'course_code',
                'type' => 'string',
                'label' => ''
            ];

            $newFields[] = [
                'key' => 'TrainingNeeds.training_course_id',
                'field' => 'training_course_id',
                'type' => 'integer',
                'label' => ''
            ];

            $newFields[] = [
                'key' => 'TrainingCourses.description',
                'field' => 'course_description',
                'type' => 'text',
                'label' => ''
            ];

            $newFields[] = [
                'key' => 'TrainingRequirements.requirement',
                'field' => 'course_requirement',
                'type' => 'string',
                'label' => ''
            ];
        }

        if ($selectedNeedType == 'NEED') {

            $newFields[] = [
                'key' => 'TrainingNeeds.training_need_category_id',
                'field' => 'training_need_category_id',
                'type' => 'integer',
                'label' => ''
            ];

            $newFields[] = [
                'key' => 'TrainingNeeds.training_need_competency_id',
                'field' => 'training_need_competency_id',
                'type' => 'integer',
                'label' => ''
            ];

            $newFields[] = [
                'key' => 'TrainingNeeds.training_standard',
                'field' => 'training_standard',
                'type' => 'string',
                'label' => __('Training Standard')
            ];

            $newFields[] = [
                'key' => 'TrainingNeeds.training_need_sub_standard_id',
                'field' => 'training_need_sub_standard_id',
                'type' => 'integer',
                'label' => __('Training Sub Standard')
            ];

        }

        $newFields[] = [
            'key' => 'TrainingNeeds.training_priority_id',
            'field' => 'training_priority_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'TrainingNeeds.reason',
            'field' => 'reason',
            'type' => 'text',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Staff.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS No')
        ];

        /*
        $newFields[] = [
            'key' => 'TrainingNeeds.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => ''
        ];
        */
        $newFields[] = [
            'key' => 'staff_full_name',
            'field' => 'staff_full_name',
            'type' => 'string',
            'label' => __('Staff')
        ];
        $newFields[] = [
            'key' => 'gender',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
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

        if ($selectedNeedType == 'NEED') {

            $newFields[] = [
                'key' => 'StaffSubjects.subjects',
                'field' => 'staff_subjects',
                'type' => 'text',
                'label' => ''
            ];

        }

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetInstitutionCode(Event $event, Entity $entity)
    {
        //if ($entity->has('staff') && !empty($entity->staff)) { // POCOR-6597
            $InstitutionStaff = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');

            $statuses = $StaffStatuses->findCodeList();

            //get the latest institution which staff assigned on.
            $query = $InstitutionStaff->find('all')
                    ->contain(['Institutions'])
                    ->where([
                        $InstitutionStaff->aliasField('staff_id') => $entity->user_id_id,
                        $InstitutionStaff->aliasField('staff_status_id') => $statuses['ASSIGNED']
                    ])
                    ->order([
                        $InstitutionStaff->aliasField('start_date DESC'),
                        $InstitutionStaff->aliasField('created DESC')
                    ])
                    ->first();

            if (!empty($query)) {
                $entity->institution_id = $query->institution->id;
                $entity->institution_name = $query->institution->name;
                return $query->institution->code;
            }
        //} // POCOR-6597
    }

    public function onExcelGetInstitutionName(Event $event, Entity $entity)
    {
        if ($entity->has('institution_name')) {
            return $entity->institution_name;
        }
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

    public function onExcelGetIdentityNumber(Event $event, Entity $entity)
    {
        return $entity->custom_identity_number;
    }

    public function onExcelGetOtherIdentity(Event $event, Entity $entity)
    {
        return $entity->custom_identity_other_data;
    }

    public function onExcelGetGender(Event $event, Entity $entity)
    {
        $gender = TableRegistry::get('User.Genders');
        $gender_data = $gender->find()->select(['name'])->where([$gender->aliasField('id') => $entity->staff_user_gender_id])->first(); // POCOR-6597
        return $gender_data->name;
    }

    public function onExcelGetStaffFullName(Event $event, Entity $entity)
    {
        return $entity->staff_full_name; // POCOR-6597
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

    public function onExcelGetOpenemisNo(Event $event, Entity $entity)
    {
        // START: POCOR-6597
        $Users = TableRegistry::get('User.Users');
        $user_data = $Users->findById($entity->user_id_id)->first();
        $entity->staff_full_name = $user_data->first_name .' '. $user_data->middle_name .' '. $user_data->third_name .' '. $user_data->last_name;
        $entity->staff_user_gender_id = $user_data->gender_id;
        return $user_data->openemis_no;
        // END: POCOR-6597
    }

    public function onExcelGetStaffSubjects(Event $event, Entity $entity)
    {
        $return = [];
        
        if ($entity->has('institution_id') && $entity->has('staff')) {
            if(!empty($entity->institution_id) && !empty($entity->staff)) {
                $StaffSubjects = TableRegistry::get('Staff.StaffSubjects');
                $query = $StaffSubjects->find()
                        ->contain([
                            'InstitutionSubjects.EducationSubjects'
                        ])
                        ->where([
                            $StaffSubjects->aliasField('institution_id') => $entity->institution_id,
                            $StaffSubjects->aliasField('staff_id') => $entity->staff->id
                        ])
                        ->toArray();
                
                $subjects = [];
                foreach ($query as $key => $value) {
                    if ($value->has('institution_subject') && !empty($value->institution_subject)) {
                        if ($value->institution_subject->has('education_subject') && !empty($value->institution_subject->education_subject)) {
                            $subjects[] = $value->institution_subject->education_subject->code_name;
                        }
                    }
                }

                //for future implementation, enable change line for each of the subjects
                // $return['value'] = "=\"" . implode(",\n", $subjects) . "\"";
                // $return['style'] = ['wrap_text' => true];

                $return = implode(", ", $subjects);

            }
        }

        return $return;
    }
}
