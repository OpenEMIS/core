<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use PHPExcel_Worksheet;

use App\Model\Table\AppTable;

class ImportCompetencyResultsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.ImportCompetencyResult', [
            'plugin' => 'Institution',
            'model' => 'InstitutionCompetencyResults',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentCompetencies']
        ]);

        // register table once
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $this->CompetencyTemplates = TableRegistry::getTableLocator()->get('Competency.CompetencyTemplates');
        $this->CompetencyPeriods = TableRegistry::getTableLocator()->get('Competency.CompetencyPeriods');
        $this->addBehavior('Institution.InstitutionTab');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->notEmpty(['academic_period', 'competency_template', 'class', 'competency_period', 'competency_item', 'select_file']);
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        $request = $this->request;
        if (empty($request->getQuery('competency_item'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        $request = $this->request;
        $query = $request->getQuery(); // Get the query parameters

        // Unset specific query parameters
        unset($query['period']);
        unset($query['class']);
        unset($query['competency_template']);
        unset($query['competency_item']);
        unset($query['competency_period']);

        // Set the modified query parameters back to the request
        $request = $request->withQueryParams($query);
        $this->request = $request;
    }


    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        $this->dependency = [];
        //$this->dependency["academic_period"] = ["class"];
        $this->dependency["class"] = ["competency_template"];
        $this->dependency["competency_template"] = ["competency_period"];
        $this->dependency["competency_period"] = ["competency_item"];
        $this->dependency["competency_item"] = ["select_file"];

        $this->ControllerAction->field('academic_period', ['type' => 'select']);
        $this->ControllerAction->field('class', ['type' => 'select']);
        $this->ControllerAction->field('competency_template', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('competency_period', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('competency_item', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['academic_period', 'class', 'competency_template', 'competency_period', 'competency_item', 'select_file']);

        //Assumptiopn - onChangeReload must be named in this format: change<field_name>. E.g changeClass
        $currentFieldName = strtolower(str_replace("change", "", $entity->submit));

        if (isset($this->request->getData()[$this->getAlias()])) {
            $unsetFlag = false;
            $aryRequestData = $this->request->getData()[$this->getAlias()];
            foreach ($aryRequestData as $requestData => $value) {
                $query = $this->request->getQuery(); // Get the query parameters
                $data = $this->request->getData(); // Get the request data

                if ($unsetFlag) {
                    unset($query[$requestData]);
                    $data[$this->getAlias()][$requestData] = 0;
                }

                if ($currentFieldName == str_replace("_", "", $requestData)) {
                    $unsetFlag = true;
                }

                // Set the modified query parameters and request data back to the request
                $this->request = $this->request->withQueryParams($query);
                $this->request = $this->request->withParsedBody($data);
            }

            $aryRequestData = $this->request->getData()[$this->getAlias()];
            foreach ($aryRequestData as $requestData => $value) {
    if (isset($this->dependency[$requestData]) && $value) {
        $aryDependencies = $this->dependency[$requestData];
        foreach ($aryDependencies as $dependency) {
            $requestDataArray = $this->request->getData()[$this->getAlias()]; // Get request data
            $this->request = $this->request->withQueryParams($requestDataArray); // Set modified query parameters

            $this->ControllerAction->field($dependency, ['visible' => true]);
        }
    }
}
        }
    }

    public function onUpdateFieldAcademicPeriod(EventInterface $event, array $attr, $action, ServerRequest $request)
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

   public function addEditOnChangeAcademicPeriod(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('academic_period', $request->getData()[$this->getAlias()])) {
                    $requestData = $request->getData(); // Get request data
                    $requestData[$this->getAlias()]['period'] = $requestData[$this->getAlias()]['academic_period'];
                    $this->request = $this->request->withParsedBody($requestData); // Set modified request data
                }
            }
        }
    }


   public function onUpdateFieldClassBAK(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->getQuery('period')) ? $request->getQuery('period') : $this->AcademicPeriods->getCurrent();
            $institutionId = !empty($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $this->getInstitutionID();
            $userId = $this->Auth->user('id');
            $AccessControl = $this->AccessControl;
            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $roles = $Institutions->getInstitutionRoles($userId, $institutionId);
            $query = $InstitutionClasses->find();
            if (!$AccessControl->isAdmin()) {
                if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles) && !$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles)) {
                    $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
                    $subjectPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
                    if (!$classPermission && !$subjectPermission) {
                        $query->where(['1 = 0'], [], true);
                    } else {
                        // $query->innerJoin(['ClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                        //     'ClassesSecondaryStaff.institution_class_id = InstitutionClasses.id'
                        // ]);
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
                                        //['ClassesSecondaryStaff.secondary_staff_id' => $userId],
                                        //['InstitutionSubjectStaff.staff_id' => $userId]
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
            //echo '<pre>';print_r($query->toArray());die;
            $classOptions = $query
                ->find('list')
                ->where([
                    $InstitutionClasses->aliasField('academic_period_id') =>$academicPeriodId,
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

    public function onUpdateFieldClass(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->getQuery('period')) ? $request->getQuery('period') : $this->AcademicPeriods->getCurrent();
            $institutionId = !empty($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $this->getInstitutionID();
            $userId = $this->Auth->user('id');
            $AccessControl = $this->AccessControl;
            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $roles = $Institutions->getInstitutionRoles($userId, $institutionId);
            $query = $InstitutionClasses->find();
            if (!$AccessControl->isAdmin()) {
                //if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles) && !$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles)) {
                    $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
                    $subjectPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
                    if (!$classPermission && !$subjectPermission) {
                        $query->where(['1 = 0'], [], true);
                    } else {

                        $InstitutionClassesSecondaryStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionClassesSecondaryStaff');
                        $classData = $InstitutionClassesSecondaryStaff->find()
                        ->select([$InstitutionClassesSecondaryStaff->aliasField('institution_class_id')])
                        ->where([$InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id') => $userId])->toArray();

                        $classIds = [];

                        if (!empty($classData)) {
                            foreach ($classData as $key => $value) {
                                $classIds[] = $value->institution_class_id;
                            }
                        }

                        if (!empty($classIds)) {
                            $query->where([
                                    'OR' => [
                                        ['InstitutionClasses.id IN' => $classIds],
                                        ['InstitutionClasses.staff_id' => $userId],
                                    ]
                                ]);
                        } else {
                           $query->where([
                                    'OR' => [
                                        ['InstitutionClasses.staff_id' => $userId],
                                    ]
                                ]);
                        }
                    }
                //}
            }

            //echo '<pre>';print_r($query->toArray());die;
            $classOptions = $query
                ->find('list')
                ->where([
                    $InstitutionClasses->aliasField('academic_period_id') =>$academicPeriodId,
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

    public function addEditOnChangeClass(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        //echo "<pre>"; print_r($request);die;
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                $requestData = $request->getData()[$this->getAlias()]; // Get request data for the alias
                if (isset($requestData['academic_period'])) {
                    $requestData['period'] = $requestData['academic_period'];
                    $request->getData()[$this->getAlias()] = $requestData; // Modify request data
                }
                if (array_key_exists('class', $request->getData()[$this->getAlias()])) {
                    $requestData['class'] = $request->getData()['ImportCompetencyResults']['class'];
                    $request->getData()[$this->getAlias()] = $requestData;
                }
            }
        }
    }


    public function onUpdateFieldCompetencyTemplate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if ($action == 'add') {
            $academicPeriodId = !is_null($this->request->getQuery('period')) ? $this->request->getQuery('period') : $this->AcademicPeriods->getCurrent();
            $getclassId = $this->request->getData('ImportCompetencyResults')['class'];
            $classId = $getclassId !== null ? $getclassId : null;
            $institutionId = !empty($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $this->getInstitutionID();

            $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
            $educationGrades = $InstitutionClassGrades->find()
                ->where([$InstitutionClassGrades->aliasField('institution_class_id IS') => $classId])
                ->extract('education_grade_id')
                ->toArray();

            $templateOptions = [];
            if (!empty($educationGrades)) {
                $templateOptions = $this->CompetencyTemplates
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([
                        $this->CompetencyTemplates->aliasField('academic_period_id') => $academicPeriodId,
                        $this->CompetencyTemplates->aliasField('education_grade_id IN') => $educationGrades
                    ])
                    ->order([$this->CompetencyTemplates->aliasField('code')])
                    ->toArray();
            }
             //echo "<pre>"; print_r($templateOptions);die;

            $attr['options'] = $templateOptions;
            $attr['onChangeReload'] = 'changeCompetencyTemplate';
        }
        return $attr;
    }

    public function onUpdateFieldCompetencyPeriod(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriod action=' . json_encode($action)); //[TEMP-LOG]
            Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriod $request->getQuery(period)=' . json_encode($request->getQuery('period'))); //[TEMP-LOG]
            Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriod $this->request->getQuery(period)=' . json_encode($this->request->getQuery('period'))); //[TEMP-LOG]
            Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriod $this->request->getQuery(academic_period)=' . json_encode($this->request->getQuery('academic_period'))); //[TEMP-LOG]
            Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriod $this->request->getQuery(competency_template)=' . json_encode($this->request->getQuery('competency_template'))); //[TEMP-LOG]

            //POCOR-9584: start - use $this->request (updated by addAfterAction via withQueryParams),
            //   check academic_period key first (set from POST data), period as fallback (URL query string on initial load)
            $academicPeriodId = $this->request->getQuery('academic_period')
                ?? $this->request->getQuery('period')
                ?? $this->AcademicPeriods->getCurrent();
            $competencyTemplateId = $this->request->getQuery('competency_template');
            //POCOR-9584: end
            Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriod $academicPeriodId=' . json_encode($academicPeriodId)); //[TEMP-LOG]
            Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriod competency_template is null=' . json_encode(is_null($competencyTemplateId))); //[TEMP-LOG]

            $competencyPeriodOptions = [];
            if (!is_null($competencyTemplateId)) { //POCOR-9584: use $competencyTemplateId from $this->request
                $competencyPeriodOptions = $this->CompetencyPeriods
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([
                        $this->CompetencyPeriods->aliasField('academic_period_id') => $academicPeriodId,
                        $this->CompetencyPeriods->aliasField('competency_template_id') => $competencyTemplateId //POCOR-9584: fix trailing space in column name
                    ])
                    ->toArray();
                Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriod $competencyPeriodOptions count=' . json_encode(count($competencyPeriodOptions))); //[TEMP-LOG]
            }

            $attr['options'] = $competencyPeriodOptions;
            $attr['onChangeReload'] = 'changeCompetencyPeriod';
        }
        return $attr;
    }

    public function onUpdateFieldCompetencyItem(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $competencyItemsPeriodsTable = TableRegistry::getTableLocator()->get('Competency.CompetencyItemsPeriods');
            $competencyCriteriasTable = TableRegistry::getTableLocator()->get('Competency.CompetencyCriterias');
            $conditions = [];
            if (!empty($request->getData()[$this->getAlias()]['academic_period']) && !empty($request->getData()[$this->getAlias()]['competency_template']) && !empty($request->getData()[$this->getAlias()]['competency_period'])) {
                $conditions[] = [
                    $competencyItemsPeriodsTable->aliasField('academic_period_id') => $request->getData()[$this->getAlias()]['academic_period'],
                    $competencyItemsPeriodsTable->aliasField('competency_template_id') => $request->getData()[$this->getAlias()]['competency_template'],
                    $competencyItemsPeriodsTable->aliasField('competency_period_id') => $request->getData()[$this->getAlias()]['competency_period']
                ];
            }

            $competencyItemOptions = $competencyItemsPeriodsTable->find()
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->select([
                    'id' => $competencyItemsPeriodsTable->Items->aliasField('id'),
                    'name' => $competencyItemsPeriodsTable->Items->aliasField('name')
                ])
                ->contain(['Items'])
                ->contain(['Periods'])
                ->innerJoin([$competencyCriteriasTable->getAlias() => $competencyCriteriasTable->getTable()], [
                             $competencyCriteriasTable->aliasField('academic_period_id = ') . $competencyItemsPeriodsTable->aliasField('academic_period_id'),
                             $competencyCriteriasTable->aliasField('competency_template_id = ') . $competencyItemsPeriodsTable->aliasField('competency_template_id'),
                             $competencyCriteriasTable->aliasField('competency_item_id = ') . $competencyItemsPeriodsTable->aliasField('competency_item_id')
                            ])
                ->where($conditions)
                ->group([$competencyItemsPeriodsTable->aliasField('id')])
                ->order([$competencyItemsPeriodsTable->Items->aliasField('id')])
                ->toArray();

            $attr['options'] = $competencyItemOptions;
            $attr['onChangeReload'] = 'changeCompetencyItem';
        }

        return $attr;
    }

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $requestData = $this->request->getData()[$this->getAlias()];
        $tempRow['academic_period_id'] = $requestData['academic_period'];
        $tempRow['competency_template_id'] = $requestData['competency_template'];
        $tempRow['competency_period_id'] = $requestData['competency_period'];
        $tempRow['competency_item_id'] = $requestData['competency_item'];
        $tempRow['institution_id'] = !empty($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $this->getInstitutionID();

        return true;
    }

    public function getStudentArray()
    {
        //POCOR-9584: start - for template action (GET) class is in encoded pass[1] as class_id;
        //   getQueryString() decodes pass[1], getQuery('class') is used on POST (addAfterAction withQueryParams)
        $qs = $this->getQueryString();
        $classId = $this->request->getQuery('class') ?? ($qs['class_id'] ?? null);
        //POCOR-9584: end

        if (empty($classId)) { //POCOR-9584: guard — CakePHP5 throws on null WHERE value
            return [];
        }

        $institutionClassStudentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $studentStatusesTable = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $arrayStudent = $institutionClassStudentsTable->find()
            ->select([
                $institutionClassStudentsTable->Users->aliasField('openemis_no'),
                $institutionClassStudentsTable->Users->aliasField('first_name'),
                $institutionClassStudentsTable->Users->aliasField('middle_name'),
                $institutionClassStudentsTable->Users->aliasField('third_name'),
                $institutionClassStudentsTable->Users->aliasField('last_name'),
                $institutionClassStudentsTable->Users->aliasField('preferred_name'),
            ])
            ->matching('Users')
            ->matching('InstitutionClasses')
            ->matching('EducationGrades')
            ->matching($studentStatusesTable->getAlias(), function ($q) use ($studentStatusesTable) {
                return $q->where([$studentStatusesTable->aliasField('code') => 'CURRENT']);
            })
            ->where([
                $institutionClassStudentsTable->aliasField('institution_class_id') => $classId
            ])
            ->order([
                $institutionClassStudentsTable->Users->aliasField('first_name'),
                $institutionClassStudentsTable->Users->aliasField('last_name')
            ])
            ->toArray();

        return $arrayStudent;
    }

    public function getCompetencyCriteriasArray()
    {
        //POCOR-9584: start - for template action (GET) values come from encoded pass[1] via getQueryString();
        //   for add POST they come from addAfterAction withQueryParams (short keys: academic_period, competency_template)
        $qs = $this->getQueryString();
        $academicPeriod    = $this->request->getQuery('academic_period')    ?? ($qs['academic_period_id']    ?? null);
        $competencyItem    = $this->request->getQuery('competency_item')    ?? ($qs['competency_item_id']    ?? null);
        $competencyTemplate = $this->request->getQuery('competency_template') ?? ($qs['competency_template_id'] ?? null);
        //POCOR-9584: end

        if (empty($academicPeriod) || empty($competencyTemplate)) { //POCOR-9584: guard — CakePHP5 throws on null WHERE value
            return [];
        }

        $competencyCriteriasTable = TableRegistry::getTableLocator()->get('Competency.CompetencyCriterias');
        $arrayCompetencyCriterias = $competencyCriteriasTable->find()
        ->where([
            $competencyCriteriasTable->aliasField('academic_period_id') => $academicPeriod,
            $competencyCriteriasTable->aliasField('competency_template_id') => $competencyTemplate
        ])
        ->toArray();

        //POCOR-9584: filter by competency_item_id if provided (may be absent on initial template load)
        if (!empty($competencyItem)) {
            $arrayCompetencyCriterias = array_filter(
                $arrayCompetencyCriterias,
                fn($row) => $row->competency_item_id == $competencyItem
            );
            $arrayCompetencyCriterias = array_values($arrayCompetencyCriterias);
        }

        return $arrayCompetencyCriterias;
    }
}

