<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;
use Cake\Core\Configure;

class InstitutionRubricsTable extends AppTable
{
    use OptionsTrait;
    use MessagesTrait;
    private $_fieldOrder = [];
    private $_contain = ['EducationGrades.EducationProgrammes'];

    public function initialize(array $config)
    {
        $this->table('institution_quality_rubrics');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Classes', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('Subjects', ['className' => 'Institution.InstitutionSubjects', 'foreignKey' => 'institution_subject_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->hasMany('InstitutionRubricAnswers', ['className' => 'Institution.InstitutionRubricAnswers', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Excel', ['excludes' => ['status', 'comment'], 'pages' => ['view']]);
        if (!Configure::read('schoolMode')) {    
            $this->addBehavior('Report.RubricsReport');
        }
    }

    public function beforeAction(Event $event)
    {
        $this->ControllerAction->field('status', ['visible' => ['index' => false, 'view' => true, 'edit' => false]]);
        $this->ControllerAction->field('comment', ['visible' => false]);
        $this->ControllerAction->field('institution_class_id', ['visible' => ['index' => false, 'view' => false, 'edit' => true]]);
    }

    public function afterAction(Event $event, ArrayObject $config)
    {
        $this->ControllerAction->setFieldOrder($this->_fieldOrder);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $templateId = $this->get($settings['id'])->rubric_template_id;
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'portrait',
            'templateId' => $templateId,
        ];
        $event->stopPropagation();
    }

    public function onGetCustomRubricSectionsElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $value = '';

        if ($action == 'view') {
            $Form = $event->subject()->Form;
            $status = $this->get($entity->id)->status;

            $tableHeaders = [];
            $tableCells = [];

            if ($status == 1) {
                $tableHeaders = [__('No.'), __('Name'), __('No of Criterias (Answered)')];
            } else {
                $tableHeaders = [__('No.'), __('Name'), __('No of Criterias')];
            }

            $RubricSections = $this->RubricTemplates->RubricSections;
            $RubricCriterias = $this->RubricTemplates->RubricSections->RubricCriterias;

            $results = $RubricSections
                ->find()
                ->find('order')
                ->contain(['RubricCriterias'])
                ->where([$RubricSections->aliasField('rubric_template_id') => $entity->rubric_template_id])
                ->all();

            if (!$results->isEmpty()) {
                $data = $results->toArray();

                $count = 1;
                foreach ($data as $key => $obj) {
                    $rowData = [];
                    $rubricSectionId = $obj->id;
                    $rubricSectionName = $obj->name;
                    if ($this->AccessControl->check([$this->controller->name, 'RubricAnswers', 'edit'])) {
                        $editable = $this->AcademicPeriods->getEditable($entity->academic_period_id);
                        $status = $this->get($entity->id)->status;
                        if ($editable || $status == 2) {
                            $rubricSectionName = $event->subject()->Html->link($obj->name, [
                                'plugin' => $this->controller->plugin,
                                'controller' => $this->controller->name,
                                'action' => 'RubricAnswers',
                                'edit',
                                $this->paramsEncode(['id' => $entity->id]),
                                'status' => $status,
                                'section' => $rubricSectionId
                            ]);
                        }
                    }
                    $criterias = $RubricCriterias
                        ->find()
                        ->where([
                            $RubricCriterias->aliasField('rubric_section_id') => $rubricSectionId,
                            $RubricCriterias->aliasField('type !=') => 1
                        ])
                        ->count();
                    $noOfCriterias = $criterias;

                    // Rubric Answers
                    $rubricAnswers = $this->InstitutionRubricAnswers
                        ->find()
                        ->where([
                            $this->InstitutionRubricAnswers->aliasField('institution_quality_rubric_id') => $entity->id,
                            $this->InstitutionRubricAnswers->aliasField('rubric_section_id') => $rubricSectionId,
                            $this->InstitutionRubricAnswers->aliasField('rubric_criteria_option_id IS NOT') => 0
                        ]);
                    if ($status == 1) {
                        $noOfCriterias .= ' (' . $rubricAnswers->count() . ')';
                    }
                    // End

                    $rowData[0] = $count;
                    $rowData[1] = $rubricSectionName;
                    $rowData[2] = $noOfCriterias;

                    $tableCells[$key] = $rowData;
                    $count++;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;

            $value = $event->subject()->renderElement('Institution.Rubrics/sections', ['attr' => $attr]);
        }

        return $value;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'education_grade_id') {
            return __('Programme') . '<span class="divider"></span>' . __('Grade');
        } else if ($field == 'institution_subject_id') {
            return __('Class') . '<span class="divider"></span>' . __('Subject');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetEducationGradeId(Event $event, Entity $entity)
    {
        return $entity->education_grade->education_programme->name . '<span class="divider"></span>' . $entity->education_grade->name;
    }

    public function onGetInstitutionSubjectId(Event $event, Entity $entity)
    {
        return $entity->class->name . '<span class="divider"></span>' . $entity->subject->name;
    }

    public function onGetLastModified(Event $event, Entity $entity)
    {
        return $this->formatDate($entity->modified);
    }

    public function onGetToBeCompletedBy(Event $event, Entity $entity)
    {
        $value = '<i class="fa fa-minus"></i>';

        $RubricStatuses = $this->RubricTemplates->RubricStatuses;
        $results = $RubricStatuses
            ->find()
            ->select([
                $RubricStatuses->aliasField('date_disabled')
            ])
            ->where([
                $RubricStatuses->aliasField('rubric_template_id') => $entity->rubric_template->id
            ])
            ->join([
                'table' => 'rubric_status_periods',
                'alias' => 'RubricStatusPeriods',
                'conditions' => [
                    'RubricStatusPeriods.rubric_status_id =' . $RubricStatuses->aliasField('id'),
                    'RubricStatusPeriods.academic_period_id' => $entity->academic_period_id
                ]
            ])
            ->all();

        if (!$results->isEmpty()) {
            $dateDisabled = $results->first()->date_disabled;
            $value = $this->formatDate($dateDisabled);
        }

        return $value;
    }

    public function onGetCompletedOn(Event $event, Entity $entity)
    {
        return $this->formatDateTime($entity->modified);
    }

    public function indexBeforeAction(Event $event)
    {
        list($statusOptions, $selectedStatus) = array_values($this->_getSelectOptions());

        $plugin = $this->controller->plugin;
        $controller = $this->controller->name;
        $action = $this->alias;

        $tabElements = [];
        if ($this->AccessControl->check([$this->controller->name, 'NewRubrics', 'view'])) {
            $tabElements[__('New')] = [
                'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => $action, 'status' => 0],
                'text' => __('New')
            ];
            $tabElements[__('Draft')] = [
                'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => $action, 'status' => 1],
                'text' => __('Draft')
            ];
        }

        if ($this->AccessControl->check([$this->controller->name, 'CompletedRubrics', 'view'])) {
            $tabElements[__('Completed')] = [
                'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => $action, 'status' => 2],
                'text' => __('Completed')
            ];
        }
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $statusOptions[$selectedStatus]);

        $this->_fieldOrder = ['rubric_template_id', 'academic_period_id', 'education_grade_id', 'institution_subject_id', 'staff_id'];
        if ($selectedStatus == 0) {     //New
            $this->ControllerAction->field('to_be_completed_by');
            $this->_fieldOrder[] = 'to_be_completed_by';
            $this->_buildRecords();
        } else if ($selectedStatus == 1) {  //Draft
            $this->ControllerAction->field('last_modified');
            $this->ControllerAction->field('to_be_completed_by');
            $this->_fieldOrder[] = 'last_modified';
            $this->_fieldOrder[] = 'to_be_completed_by';
        } else if ($selectedStatus == 2) {  //Completed
            $this->ControllerAction->field('completed_on');
            $this->_fieldOrder[] = 'completed_on';
        }
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        list(, $selectedStatus) = array_values($this->_getSelectOptions());

        $query
            ->contain($this->_contain)
            ->where([$this->aliasField('status') => $selectedStatus]);
        $options['order'] = [$this->AcademicPeriods->aliasField('order') => 'asc'];
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query
            ->contain($this->_contain);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->ControllerAction->field('rubric_sections', [
            'type' => 'custom_rubric_sections',
            'valueClass' => 'table-full-width'
        ]);

        switch ($entity->status) {
            case 1:
                $entity->status = __('Draft');
                break;
            case 2:
                $entity->status = __('Completed');
                break;
            default:
                $entity->status = __('New');
                break;
        }

        $this->_fieldOrder = ['status', 'rubric_template_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'institution_subject_id', 'staff_id', 'rubric_sections'];
    }

    public function onBeforeDelete(Event $event, ArrayObject $options, $ids)
    {
        $rubricRecord = $this->get($ids);

        if ($rubricRecord->status == 2) {
            $ids['status'] = 1;
            $entity = $this->newEntity($ids, ['validate' => false]);
            if ($this->save($entity)) {
                $this->Alert->success('InstitutionRubricAnswers.reject.success');
            } else {
                $this->Alert->success('InstitutionRubricAnswers.reject.failed');
                $this->log($entity->errors(), 'debug');
            }

            $event->stopPropagation();
            $action = $this->ControllerAction->url('index');
            $action['status'] = 2;
            return $this->controller->redirect($action);
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        list(, $selectedStatus) = array_values($this->_getSelectOptions());
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if ($selectedStatus == 0) {     // New
            unset($buttons['remove']);
        } else if ($selectedStatus == 2) {  // Completed
            unset($buttons['edit']);
        }

        return $buttons;
    }

    public function _buildRecords()
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');

        // Update all New Rubric to Expired by Institution Id
        $this->updateAll(['status' => -1],
            [
                'institution_id' => $institutionId,
                'status' => 0
            ]
        );

        $rubrics = $this->RubricTemplates
            ->find('list')
            ->toArray();
        $todayDate = date("Y-m-d");

        $RubricStatuses = $this->RubricTemplates->RubricStatuses;
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $Subjects = TableRegistry::get('Institution.InstitutionSubjects');
        $ClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');

        foreach ($rubrics as $key => $rubric) {
            $rubricStatuses = $RubricStatuses
                ->find()
                ->contain(['AcademicPeriods', 'SecurityRoles', 'Programmes'])
                ->where([
                    $RubricStatuses->aliasField('rubric_template_id') => $key,
                    $RubricStatuses->aliasField('date_disabled >=') => $todayDate
                ])
                ->toArray();

            foreach ($rubricStatuses as $rubricStatus) {
                $statusId = $rubricStatus->id;
                $templateId = $rubricStatus->rubric_template_id;
                $programmeIds = [];
                foreach ($rubricStatus->programmes as $programme) {
                    $programmeId = $programme->id;
                    $programmeIds[$programmeId] = $programmeId;
                }

                if (!empty($programmeIds)) {
                    $gradeIds = $this->EducationGrades
                        ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                        ->where([$this->EducationGrades->aliasField('education_programme_id IN') => $programmeIds])
                        ->toArray();

                    if (!empty($gradeIds)) {
                        foreach ($rubricStatus->academic_periods as $academicPeriod) {
                            $academicPeriodId = $academicPeriod->id;
                            $classResults = $Classes
                                ->find()
                                ->select([
                                    $Classes->aliasField('id'),
                                    $Classes->aliasField('name')
                                ])
                                ->where([
                                    $Classes->aliasField('institution_id') => $institutionId,
                                    $Classes->aliasField('academic_period_id') => $academicPeriodId,
                                ])
                                ->join([
                                    'table' => $ClassGrades->_table,
                                    'alias' => $ClassGrades->alias(),
                                    'conditions' => [
                                        $ClassGrades->aliasField('institution_class_id =') . $Classes->aliasField('id'),
                                        $ClassGrades->aliasField('education_grade_id IN') => $gradeIds
                                    ]
                                ])
                                ->join([
                                    'table' => $ClassSubjects->_table,
                                    'alias' => $ClassSubjects->alias(),
                                    'conditions' => [
                                        $ClassSubjects->aliasField('institution_class_id =') . $Classes->aliasField('id')
                                    ]
                                ])
                                ->join([
                                    'table' => $Subjects->_table,
                                    'alias' => $Subjects->alias(),
                                    'conditions' => [
                                        $Subjects->aliasField('id =') . $ClassSubjects->aliasField('institution_subject_id'),
                                        $Subjects->aliasField('institution_id') => $institutionId,
                                        $Subjects->aliasField('academic_period_id') => $academicPeriodId
                                    ]
                                ])
                                ->group([
                                    $Classes->aliasField('id')
                                ])
                                ->contain(['ClassGrades', 'InstitutionSubjects.SubjectStaff'])
                                ->all();

                            if (!$classResults->isEmpty()) {
                                foreach ($classResults as $class) {
                                    $classId = $class->id;
                                    $gradeId = 0;
                                    foreach ($class->class_grades as $grade) {
                                        $gradeId = $grade->education_grade_id;
                                    }

                                    foreach ($class->institution_subjects as $subject) {
                                        $subjectId = $subject->id;
                                        foreach ($subject->subject_staff as $staff) {
                                            $staffId = $staff->staff_id;

                                            $results = $this
                                                ->find('all')
                                                ->where([
                                                    $this->aliasField('institution_id') => $institutionId,
                                                    $this->aliasField('rubric_template_id') => $templateId,
                                                    $this->aliasField('academic_period_id') => $academicPeriodId,
                                                    $this->aliasField('education_grade_id') => $gradeId,
                                                    $this->aliasField('institution_class_id') => $classId,
                                                    $this->aliasField('institution_subject_id') => $subjectId,
                                                    $this->aliasField('staff_id') => $staffId
                                                ])
                                                ->all();

                                            if ($results->isEmpty()) {
                                                // Insert New Rubric if not found
                                                $data = [
                                                    'institution_id' => $institutionId,
                                                    'rubric_template_id' => $templateId,
                                                    'academic_period_id' => $academicPeriodId,
                                                    'education_grade_id' => $gradeId,
                                                    'institution_class_id' => $classId,
                                                    'institution_subject_id' => $subjectId,
                                                    'staff_id' => $staffId
                                                ];
                                                $entity = $this->newEntity($data);

                                                if ($this->save($entity)) {
                                                } else {
                                                    $this->log($entity->errors(), 'debug');
                                                }
                                            } else {
                                                // Update Expired Rubric back to New
                                                $this->updateAll(['status' => 0],
                                                    [
                                                        'institution_id' => $institutionId,
                                                        'rubric_template_id' => $templateId,
                                                        'academic_period_id' => $academicPeriodId,
                                                        'education_grade_id' => $gradeId,
                                                        'institution_class_id' => $classId,
                                                        'institution_subject_id' => $subjectId,
                                                        'staff_id' => $staffId,
                                                        'status' => -1
                                                    ]
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function _getSelectOptions()
    {
        //Return all required options and their key
        $statusOptions = $this->getSelectOptions('Rubrics.status');
        $selectedStatus = $this->queryString('status', $statusOptions);

        // If do not have access to Rubric - New but have access to Rubric - Completed, then set selectedStatus to 2
        if (!$this->AccessControl->check([$this->controller->name, 'NewRubrics', 'view'])) {
            if ($this->AccessControl->check([$this->controller->name, 'CompletedRubrics', 'view'])) {
                $selectedStatus = 2;
            }
        }

        return compact('statusOptions', 'selectedStatus');
    }
}
