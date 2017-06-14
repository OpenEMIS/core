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

        $newFields[] = [
            'key' => 'TrainingNeeds.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => ''
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
        if ($entity->has('staff') && !empty($entity->staff)) {
            $InstitutionStaff = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');

            $statuses = $StaffStatuses->findCodeList();

            //get the latest institution which staff assigned on.
            $query = $InstitutionStaff->find('all')
                    ->contain(['Institutions'])
                    ->where([
                        $InstitutionStaff->aliasField('staff_id') => $entity->staff->id,
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
        }
    }

    public function onExcelGetInstitutionName(Event $event, Entity $entity)
    {
        if ($entity->has('institution_name')) {
            return $entity->institution_name;
        }
    }

    public function onExcelGetOpenemisNo(Event $event, Entity $entity)
    {
        if ($entity->has('staff') && !empty($entity->staff)) {
            return  $entity->staff->openemis_no;
        }
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
