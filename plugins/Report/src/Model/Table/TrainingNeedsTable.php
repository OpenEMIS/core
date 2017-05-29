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
        $this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'course_id']);
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

        $query
            ->leftJoinWith('Staff.InstitutionStaff.Institutions')
            ->contain(['TrainingNeedSubStandards.TrainingNeedStandards', 'Courses.TrainingRequirements']);

        if ($selectedStatus != '-1') {
            $query->matching('WorkflowSteps.WorkflowStatuses', function ($q) use ($selectedStatus) {
                return $q->where(['WorkflowStatuses.id' => $selectedStatus]);
            });
        }

        $query->select([
            'institution_id' => 'Institutions.id',
            'institution_name' => 'Institutions.name',
            'institution_code' => 'Institutions.code',
            'course_code' => 'Courses.code',
            'course_description' => 'Courses.description',
            'course_requirement' => 'TrainingRequirements.name',
            'training_standard' => 'TrainingNeedStandards.name',
            'openemis_no' => 'Staff.openemis_no'
        ]);

        if ($selectedNeedType == 'CATALOGUE') {
            $query->where([$this->aliasField('training_need_category_id') => 0]);
        } elseif ($selectedNeedType == 'NEED') {
            $query->where([$this->aliasField('course_id') => 0]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $selectedNeedType = $requestData->training_need_type;

        $newFields = [];

        $newFields[] = [
            'key' => 'Institutions.institution',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => ''
        ];

        if ($selectedNeedType == 'CATALOGUE') {

            $newFields[] = [
                'key' => 'TrainingNeeds.course_id',
                'field' => 'course_id',
                'type' => 'integer',
                'label' => ''
            ];

            $newFields[] = [
                'key' => 'Courses.description',
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
            'key' => 'TrainingNeeds.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => ''
        ];

        if ($selectedNeedType == 'NEED') {

            $newFields[] = [
                'key' => 'StaffSubjects.subjects',
                'field' => 'staff_subjects',
                'type' => 'string',
                'label' => ''
            ];

        }

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetInstitutionName(Event $event, Entity $entity)
    {
        if ($entity->has('institution_name') && $entity->has('institution_code')) {
            if (!empty($entity->institution_code)) {
                $institution_code_name = $entity->institution_code . ' - ';
            }

            if (!empty($entity->institution_name)) {
                $institution_code_name .= $entity->institution_name;
            }

            return $institution_code_name;
        }
    }

    public function onExcelGetCourseId(Event $event, Entity $entity)
    {
        if ($entity->has('course') && $entity->has('course_code')) {
            if (!empty($entity->course_code)) {
                $course_code_name = $entity->course_code . ' - ';
            }

            if (!empty($entity->course)) {
                $course_code_name .= $entity->course->name;
            }

            return $course_code_name;
        }
    }

    public function onExcelGetStaffId(Event $event, Entity $entity)
    {
        if ($entity->has('openemis_no') && $entity->has('staff')) {
            if (!empty($entity->openemis_no)) {
                $staff_details = $entity->openemis_no . ' - ';
            }

            if (!empty($entity->staff)) {
                $staff_details .= $entity->staff->name;
            }

            return $staff_details;
        }
    }

    public function onExcelGetStaffSubjects(Event $event, Entity $entity)
    {
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

                return implode(', ', $subjects);
            }
        }
    }
}
