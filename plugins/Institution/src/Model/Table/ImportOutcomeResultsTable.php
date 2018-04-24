<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use PHPExcel_Worksheet;

use App\Model\Table\AppTable;

class ImportOutcomeResultsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'InstitutionOutcomeResults',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentOutcomes']
        ]);

        // register table once
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->EducationGrades = TableRegistry::get('Education.EducationGrades');
        $this->StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $this->EducationSubjects = TableRegistry::get('Education.EducationSubjects');
        $this->OutcomeTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
        $this->OutcomePeriods = TableRegistry::get('Outcome.OutcomePeriods');
        $this->OutcomeCriterias = TableRegistry::get('Outcome.OutcomeCriterias');
        $this->OutcomeGradingTypes = TableRegistry::get('Outcome.OutcomeGradingTypes');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateOutcomeCriteriasData'] = 'onImportPopulateOutcomeCriteriasData';
        $events['Model.import.onImportPopulateUsersData'] = 'onImportPopulateUsersData';
        $events['Model.import.onImportPopulateOutcomeGradingOptionsData'] = 'onImportPopulateOutcomeGradingOptionsData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->notEmpty(['academic_period', 'outcome_template', 'outcome_period', 'select_file']);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $request = $this->request;
        if (empty($request->query('template')) || empty($request->query('outcome_period'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $request = $this->request;
        unset($request->query['period']);
        unset($request->query['class']);
        unset($request->query['template']);
        unset($request->query['outcome_period']);
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->ControllerAction->field('academic_period', ['type' => 'select']);
        $this->ControllerAction->field('class', ['type' => 'select']);
        $this->ControllerAction->field('outcome_template', ['type' => 'select']);
        $this->ControllerAction->field('outcome_period', ['type' => 'select']);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['academic_period', 'class', 'outcome_template', 'outcome_period', 'select_file']);
    }

    public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        }
        return $attr;
    }

    public function addOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['period']);
        unset($request->query['class']);
        unset($request->query['template']);
        unset($request->query['outcome_period']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period'];
                }
                if (array_key_exists('class', $request->data[$this->alias()])) {
                    unset($request->data[$this->alias()]['class']);
                }
                if (array_key_exists('outcome_template', $request->data[$this->alias()])) {
                    unset($request->data[$this->alias()]['outcome_template']);
                }
                if (array_key_exists('outcome_period', $request->data[$this->alias()])) {
                    unset($request->data[$this->alias()]['outcome_period']);
                }
            }
        }
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();
            $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

        $userId = $this->Auth->user('id');
        $AccessControl = $this->AccessControl;
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $query = $InstitutionClasses->find();
            $classOptions = $InstitutionClasses
                ->find('list')
                ->find('byGrades', [
                    'education_grade_id' => -1, // All Grades
                ])
                ->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->controller]) // restrict user to see own class if permission is set
                ->select([
                    'id',
                    'name',
                    'education_stage_order' => $query->func()->min('EducationStages.order')
                ])
                ->where([
                    $InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                    $InstitutionClasses->aliasField('institution_id') => $institutionId
                ])
                ->order([
                    'education_stage_order',
                    $InstitutionClasses->aliasField('name') => 'ASC'
                ])
                ->group([
                    $InstitutionClasses->aliasField('id')
                ])
                ->toArray();

            $attr['options'] = $classOptions;
            $attr['onChangeReload'] = 'changeClass';
        }
        return $attr;
    }

    public function addOnChangeClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['class']);
        unset($request->query['template']);
        unset($request->query['outcome_period']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('class', $request->data[$this->alias()])) {
                    $request->query['class'] = $request->data[$this->alias()]['class'];
                }
                if (array_key_exists('outcome_template', $request->data[$this->alias()])) {
                    unset($request->data[$this->alias()]['outcome_template']);
                }
                if (array_key_exists('outcome_period', $request->data[$this->alias()])) {
                    unset($request->data[$this->alias()]['outcome_period']);
                }
            }
        }
    }

    public function onUpdateFieldOutcomeTemplate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();
            $classId = $request->query('class');
            $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

            // if class id is not null, then filter Outcome Template by class_grades of the class else by institution_grades of the school
            if (!is_null($classId) && !empty($classId)) {
                $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
                $educationGrades = $InstitutionClassGrades->find()
                    ->where([$InstitutionClassGrades->aliasField('institution_class_id') => $classId])
                    ->extract('education_grade_id')
                    ->toArray();
            } else {
                $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
                $educationGrades = $InstitutionGrades->find()
                    ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
                    ->extract('education_grade_id')
                    ->toArray();
            }

            $templateOptions = [];
            if (!empty($educationGrades)) {
                $templateOptions = $this->OutcomeTemplates
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([
                        $this->OutcomeTemplates->aliasField('academic_period_id') => $academicPeriodId,
                        $this->OutcomeTemplates->aliasField('education_grade_id IN') => $educationGrades
                    ])
                    ->order([$this->OutcomeTemplates->aliasField('code')])
                    ->toArray();
            }

            $attr['options'] = $templateOptions;
            $attr['onChangeReload'] = 'changeOutcomeTemplate';
        }
        return $attr;
    }

    public function addOnChangeOutcomeTemplate(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['template']);
        unset($request->query['outcome_period']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('outcome_template', $request->data[$this->alias()])) {
                    $request->query['template'] = $request->data[$this->alias()]['outcome_template'];
                }
                if (array_key_exists('outcome_period', $request->data[$this->alias()])) {
                    unset($request->data[$this->alias()]['outcome_period']);
                }
            }
        }
    }

    public function onUpdateFieldOutcomePeriod(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();

            $outcomePeriodOptions = [];
            if (!is_null($request->query('template'))) {
                $outcomePeriodOptions = $this->OutcomePeriods
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([
                        $this->OutcomePeriods->aliasField('academic_period_id') => $academicPeriodId,
                        $this->OutcomePeriods->aliasField('outcome_template_id ') => $request->query('template')
                    ])
                    ->toArray();
            }

            $attr['options'] = $outcomePeriodOptions;
            $attr['onChangeReload'] = 'changeOutcomePeriod';
        }
        return $attr;
    }

    public function addOnChangeOutcomePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['outcome_period']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('outcome_period', $request->data[$this->alias()])) {
                    $request->query['outcome_period'] = $request->data[$this->alias()]['outcome_period'];
                }
            }
        }
    }

    public function onUpdateFieldSelectFile(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (!empty($request->query('template')) && !empty($request->query('outcome_period'))) {
                $attr['visible'] = true;
            } else {
                $attr['visible'] = false;
            }
        }
        return $attr;
    }

    public function onImportPopulateOutcomeCriteriasData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $templateId = $this->request->query('template');
        $academicPeriodId = !is_null($this->request->query('period')) ? $this->request->query('period') : $this->AcademicPeriods->getCurrent();

        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find()
            ->select([
                $this->EducationSubjects->aliasField('name'),
                $this->EducationSubjects->aliasField('code'),
                $this->OutcomeGradingTypes->aliasField('name'),
                $lookedUpTable->aliasField('name'),
                $lookedUpTable->aliasField('code'),
                $lookedUpTable->aliasField($lookupColumn)
            ])
            ->matching($this->EducationSubjects->alias())
            ->matching($this->OutcomeGradingTypes->alias())
            ->where([
                $lookedUpTable->aliasField('outcome_template_id') => $templateId,
                $lookedUpTable->aliasField('academic_period_id') => $academicPeriodId,
            ])
            ->order([
                $this->EducationSubjects->aliasField('name'),
                $lookedUpTable->aliasField('name')
            ]);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 6;
        $data[$columnOrder]['data'][] = [__('Education Subject Name'), __('Education Subject Code'),  __('Grading Type'), $translatedReadableCol, __('Code'), $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->_matchingData[$this->EducationSubjects->alias()]->name,
                    $row->_matchingData[$this->EducationSubjects->alias()]->code,
                    $row->_matchingData[$this->OutcomeGradingTypes->alias()]->name,
                    $row->name,
                    $row->code,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $classId = $this->request->query('class');

        if (is_null($classId)) {
            unset($data[$columnOrder]);
        } else {
            $StudentStatuses = $this->StudentStatuses;
            $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
            $modelData = $this->InstitutionClassStudents->find('all')
                ->select([
                    $lookedUpTable->aliasField($lookupColumn),
                    $lookedUpTable->aliasField('first_name'),
                    $lookedUpTable->aliasField('middle_name'),
                    $lookedUpTable->aliasField('third_name'),
                    $lookedUpTable->aliasField('last_name'),
                    $lookedUpTable->aliasField('preferred_name'),
                    $lookedUpTable->aliasField('identity_number'),
                    $this->InstitutionClasses->aliasField('name'),
                    $this->EducationGrades->aliasField('name')
                ])
                ->matching($lookedUpTable->alias())
                ->matching($this->InstitutionClasses->alias())
                ->matching($this->EducationGrades->alias())
                ->matching($StudentStatuses->alias(), function ($q) use ($StudentStatuses) {
                    return $q->where([$StudentStatuses->aliasField('code') => 'CURRENT']);
                })
                ->where([
                    $this->InstitutionClassStudents->aliasField('institution_class_id') => $classId
                ])
                ->order([
                    $lookedUpTable->aliasField('first_name'),
                    $lookedUpTable->aliasField('last_name')
                ]);

            $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
            $data[$columnOrder]['lookupColumn'] = 1;
            $data[$columnOrder]['data'][] = [$translatedCol, $translatedReadableCol, __('Identity Number'), __('Class Name'), __('Education Grade')];
            if (!empty($modelData)) {
                foreach($modelData->toArray() as $row) {
                    $data[$columnOrder]['data'][] = [
                        $row->_matchingData[$lookedUpTable->alias()]->{$lookupColumn},
                        $row->_matchingData[$lookedUpTable->alias()]->name,
                        $row->_matchingData[$lookedUpTable->alias()]->identity_number,
                        $row->_matchingData[$this->InstitutionClasses->alias()]->name,
                        $row->_matchingData[$this->EducationGrades->alias()]->name
                    ];
                }
            }
        }
    }

    public function onImportPopulateOutcomeGradingOptionsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
            ->select([
                $lookedUpTable->aliasField('name'),
                $lookedUpTable->aliasField('code'),
                $lookedUpTable->aliasField($lookupColumn),
                $this->OutcomeGradingTypes->aliasField('name')
            ])
            ->matching($this->OutcomeGradingTypes->alias())
            ->order([
                $this->OutcomeGradingTypes->aliasField('name'),
                $lookedUpTable->aliasField('name')
            ]);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, __('Code'), $translatedCol, __('Grading Type')];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->code,
                    $row->{$lookupColumn},
                    $row->_matchingData[$this->OutcomeGradingTypes->alias()]->name
                ];
            }
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $requestData = $this->request->data[$this->alias()];
        $tempRow['academic_period_id'] = $requestData['academic_period'];
        $tempRow['outcome_template_id'] = $requestData['outcome_template'];
        $tempRow['outcome_period_id'] = $requestData['outcome_period'];
        $tempRow['institution_class_id'] = $requestData['class'];
        $tempRow['institution_id'] = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

        if ($tempRow->offsetExists('outcome_criteria_id') && $tempRow->offsetExists('student_id')) {
            if (!empty($tempRow['outcome_criteria_id']) && !empty($tempRow['student_id'])) {
                $outcomeCriteriaEntity = $this->OutcomeCriterias->find()
                    ->matching('Templates')
                    ->contain('OutcomeGradingTypes.GradingOptions')
                    ->where([
                        $this->OutcomeCriterias->aliasField('id') => $tempRow['outcome_criteria_id'],
                        $this->OutcomeCriterias->aliasField('outcome_template_id') => $tempRow['outcome_template_id'],
                        $this->OutcomeCriterias->aliasField('academic_period_id') => $tempRow['academic_period_id']
                    ])
                    ->first();

                if (empty($outcomeCriteriaEntity)) {
                    // criteria is not added to the template
                    $rowInvalidCodeCols['outcome_criteria_id'] = __('Outcome Criteria does not belong to this Outcome Template');
                    return false;

                } else {
                    $tempRow['education_subject_id'] = $outcomeCriteriaEntity->education_subject_id;
                    $tempRow['education_grade_id'] = $outcomeCriteriaEntity->_matchingData['Templates']->education_grade_id;

                    $Students = TableRegistry::get('Institution.Students');
                    $studentEntity = $Students->find()
                        ->where([
                            $Students->aliasField('student_id') => $tempRow['student_id'],
                            $Students->aliasField('academic_period_id') => $tempRow['academic_period_id'],
                            $Students->aliasField('education_grade_id') => $tempRow['education_grade_id'],
                            $Students->aliasField('institution_id') => $tempRow['institution_id'],
                            $Students->aliasField('student_status_id') => $this->StudentStatuses->getIdByCode('CURRENT')
                        ]);

                    if (!empty($tempRow['institution_class_id'])) {
                        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
                        
                        $studentEntity
                            ->innerJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                                 $ClassStudents->aliasField('student_id = ') . $Students->aliasField('student_id'),
                                 $ClassStudents->aliasField('academic_period_id = ') . $Students->aliasField('academic_period_id'),
                                 $ClassStudents->aliasField('education_grade_id = ') . $Students->aliasField('education_grade_id'),
                                 $ClassStudents->aliasField('institution_id = ') . $Students->aliasField('institution_id'),
                                 $ClassStudents->aliasField('student_status_id = ') . $Students->aliasField('student_status_id'),
                                 $ClassStudents->aliasField('institution_class_id') => $tempRow['institution_class_id']
                            ]);
                    }

                    if (empty($studentEntity->first())) {
                        // student is not in the education grade
                        $rowInvalidCodeCols['student_id'] = __('Student does not belong to this Education Grade/Class in this Institution');
                        return false;
                    }

                    if ($outcomeCriteriaEntity->has('outcome_grading_type')) {
                        $gradingOptions = $outcomeCriteriaEntity->outcome_grading_type->grading_options;

                        if (empty($gradingOptions)) {
                            // no grading options added
                            $rowInvalidCodeCols['outcome_criteria_id'] = __('Outcome Grading Options are not configured for ' . $outcomeCriteriaEntity->outcome_grading_type->code_name . ' Grading Type');
                            return false;

                        } else {
                            if ($tempRow->offsetExists('outcome_grading_option_id')) {
                                $selectedGradingOption = $tempRow['outcome_grading_option_id'];

                                if (strlen($selectedGradingOption) == 0) {
                                    // not allow empty for grading option
                                    $rowInvalidCodeCols['outcome_grading_option_id'] = __('This field cannot be left empty');
                                    return false;

                                } else {
                                    $valid = false;
                                    foreach ($gradingOptions as $key => $option) {
                                        if ($selectedGradingOption == $option->id) {
                                            $valid = true;
                                        }
                                    }

                                    if (!$valid) {
                                        $rowInvalidCodeCols['outcome_grading_option_id'] = __('Selected value is not a Grading Option of this Outcome Criteria');
                                        return false;
                                    }
                                }
                            }
                        }
                    } else {
                        // will never come to here unless orphan record
                        $rowInvalidCodeCols['outcome_criteria_id'] = __('Outcome Grading Type is not configured');
                        return false;
                    }
                }
            }
        }

        return true;
    }
}

