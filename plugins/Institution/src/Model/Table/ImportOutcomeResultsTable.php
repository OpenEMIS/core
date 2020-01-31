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

        $this->addBehavior('Import.ImportOutcomeResult', [
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
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->notEmpty(['academic_period','class' ,'education_subject', 'outcome_template', 'outcome_period', 'select_file']);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $request = $this->request;
        if (empty($request->query('education_subject'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $request = $this->request;
        unset($request->query['period']);
        unset($request->query['class']);
        unset($request->query['education_subject']);
        unset($request->query['outcome_template']);
        unset($request->query['outcome_period']);
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->dependency = [];
        $this->dependency["academic_period"] = ["class"];
        $this->dependency["class"] = ["outcome_template"];
        $this->dependency["outcome_template"] = ["outcome_period"];
        $this->dependency["outcome_period"] = ["education_subject"];
        $this->dependency["education_subject"] = ["select_file"];

        $this->ControllerAction->field('academic_period', ['type' => 'select']);
        $this->ControllerAction->field('class', ['type' => 'select']);
        $this->ControllerAction->field('outcome_template', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('outcome_period', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('education_subject', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['academic_period', 'class', 'outcome_template', 'outcome_period', 'education_subject', 'select_file']);

        //Assumptiopn - onChangeReload must be named in this format: change<field_name>. E.g changeClass
        $currentFieldName = strtolower(str_replace("change", "", $entity->submit));

        if (isset($this->request->data[$this->alias()])) {
            $unsetFlag = false;
            $aryRequestData = $this->request->data[$this->alias()];
            foreach ($aryRequestData as $requestData => $value) {
                if ($unsetFlag) {
                    unset($this->request->query[$requestData]);
                    $this->request->data[$this->alias()][$requestData] = 0;
                }

                if ($currentFieldName == str_replace("_", "", $requestData)) {
                    $unsetFlag = true;
                }
            }
            $aryRequestData = $this->request->data[$this->alias()];
            foreach ($aryRequestData as $requestData => $value) {
                if (isset($this->dependency[$requestData]) && $value) {
                    $aryDependencies = $this->dependency[$requestData];
                    foreach ($aryDependencies as $dependency) {
                        $this->request->query = $this->request->data[$this->alias()];
                        $this->ControllerAction->field($dependency, ['visible' => true]);
                    }
                }
            }
        }
    }

    public function onUpdateFieldEducationSubject(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            
            $conditions = [];
            if (!empty($request->data[$this->alias()]['academic_period']) && !empty($request->data[$this->alias()]['outcome_template'])) {
                $conditions[] = [
                    $this->OutcomeCriterias->aliasField('academic_period_id') => $request->data[$this->alias()]['academic_period'],
                    $this->OutcomeCriterias->aliasField('outcome_template_id') => $request->data[$this->alias()]['outcome_template']
                ];
            }

            $userId = $this->Auth->user('id');
            $AccessControl = $this->AccessControl;
            $classId = $this->request->query('class');
            $OutcomeCriterias = TableRegistry::get('Outcome.OutcomeCriterias');
            $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
            $allowedEducationSubjectList = $InstitutionSubjects
             ->find('list', [
                    'keyField' => 'education_subject_id',
                    'valueField' => 'educationSubjects'
                ])
                ->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->controller])
                ->select(['educationSubjects' => 'EducationSubjects.name', 'education_subject_id' => 'EducationSubjects.id'])
                ->contain(['EducationSubjects'])
                ->matching('ClassSubjects', function ($q) use ($classId) {
                    return $q->where(['ClassSubjects.institution_class_id' => $classId]);
                })
                ->innerJoin([$OutcomeCriterias->alias() => $OutcomeCriterias->table()], [
                             $OutcomeCriterias->aliasField('education_grade_id = ') . $InstitutionSubjects->aliasField('education_grade_id'),
                             $OutcomeCriterias->aliasField('education_subject_id = ') . $InstitutionSubjects->aliasField('education_subject_id')
                            ])
                ->where($conditions)
                ->group([
                    'EducationSubjects.id',
                ])
                ->toArray();

                $attr['options'] = $allowedEducationSubjectList;
                // useing onChangeReload to do visible
                $attr['onChangeReload'] = 'changeEducationGrade';
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        }
        return $attr;
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();
            $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

            $userId = $this->Auth->user('id');
            $AccessControl = $this->AccessControl;
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $Institutions = TableRegistry::get('Institution.Institutions');
            $roles = $Institutions->getInstitutionRoles($userId, $institutionId);
            $query = $InstitutionClasses->find();
            if (!$AccessControl->isAdmin()) {
                if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles) && !$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles)) {
                    $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
                    $subjectPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
                    if (!$classPermission && !$subjectPermission) {
                        $query->where(['1 = 0'], [], true);
                    } else {
                        $query->innerJoin(['ClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                            'ClassesSecondaryStaff.institution_class_id = InstitutionClasses.id'
                        ]);
                        // If only class permission is available but no subject permission available
                        if ($classPermission && !$subjectPermission) {
                            $query->where([
                                    'OR' => [
                                        ['InstitutionClasses.staff_id' => $userId],
                                        ['ClassesSecondaryStaff.secondary_staff_id' => $userId]
                                    ]
                                ]);
                        } else {
                            $query
                                ->innerJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                                    'InstitutionClassSubjects.institution_class_id = InstitutionClasses.id',
                                    'InstitutionClassSubjects.status = 1'
                                ])
                                ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                                    'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id'
                                ]);

                            // If both class and subject permission is available
                            if ($classPermission && $subjectPermission) {
                                $query->where([
                                    'OR' => [
                                        ['InstitutionClasses.staff_id' => $userId],
                                        ['ClassesSecondaryStaff.secondary_staff_id' => $userId],
                                        ['InstitutionSubjectStaff.staff_id' => $userId]
                                    ]
                                ]);
                            }
                            // If only subject permission is available
                            else {
                                $query->where(['InstitutionSubjectStaff.staff_id' => $userId]);
                            }
                        }
                    }
                }
            }

            $classOptions = $query
                ->find('list')
                ->where([
                    $InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                    $InstitutionClasses->aliasField('institution_id') => $institutionId])
                ->group([
                    $InstitutionClasses->aliasField('id')
                ])
                ->toArray();

                $attr['options'] = $classOptions;
                // useing onChangeReload to do visible
                $attr['onChangeReload'] = 'changeClass';
        }
        return $attr;
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
            // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeOutcomeTemplate';
        }
        return $attr;
    }

    public function onUpdateFieldOutcomePeriod(Event $event, array $attr, $action, Request $request)
    {

        if ($action == 'add') {
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();

            $outcomePeriodOptions = [];
            if (!is_null($request->query('outcome_template'))) {
                $outcomePeriodOptions = $this->OutcomePeriods
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([
                        $this->OutcomePeriods->aliasField('academic_period_id') => $academicPeriodId,
                        $this->OutcomePeriods->aliasField('outcome_template_id ') => $request->query('outcome_template')
                    ])
                    ->toArray();
            }

            $attr['options'] = $outcomePeriodOptions;
            // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeOutcomePeriod';
        }
        return $attr;
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $requestData = $this->request->data[$this->alias()];
        $tempRow['academic_period_id'] = $requestData['academic_period'];
        $tempRow['outcome_template_id'] = $requestData['outcome_template'];
        $tempRow['outcome_period_id'] = $requestData['outcome_period'];
        $tempRow['institution_class_id'] = $requestData['class'];
        $tempRow['institution_id'] = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

        $outcomeCriteriaEntity = $this->OutcomeCriterias->find()
            ->matching('Templates')
            ->contain('OutcomeGradingTypes.GradingOptions')
            ->where([
                $this->OutcomeCriterias->aliasField('id') => $tempRow['outcome_criteria_id'],
                $this->OutcomeCriterias->aliasField('outcome_template_id') => $tempRow['outcome_template_id'],
                $this->OutcomeCriterias->aliasField('academic_period_id') => $tempRow['academic_period_id']
            ])
            ->first();

            $tempRow['education_subject_id'] = $outcomeCriteriaEntity->education_subject_id;
            $tempRow['education_grade_id'] = $outcomeCriteriaEntity->_matchingData['Templates']->education_grade_id;

        return true;
    }
}

