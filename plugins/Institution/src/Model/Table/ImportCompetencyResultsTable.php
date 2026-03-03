<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

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
        //POCOR-9584: start - renamed field keys to DB column names
        return $validator
            ->notEmpty(['academic_period_id', 'institution_class_id', 'competency_template_id', 'competency_period_id', 'competency_item_id', 'select_file']);
        //POCOR-9584: end
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        $request = $this->request;
        //POCOR-9584: start - renamed competency_item → competency_item_id
        if (empty($request->getQuery('competency_item_id'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
        //POCOR-9584: end
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        $request = $this->request;
        $query = $request->getQueryParams();
        //POCOR-9584: start - renamed keys to DB column names; academic_period_id intentionally NOT cleared
        unset($query['institution_class_id']);
        unset($query['competency_template_id']);
        unset($query['competency_item_id']);
        unset($query['competency_period_id']);
        //POCOR-9584: end
        $this->request = $request->withQueryParams($query);
    }


    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        $alias = $this->getAlias();
        $this->dependency = [];
        //POCOR-9584: start - renamed all keys to DB column names; added academic_period_id → institution_class_id dependency
        $this->dependency['academic_period_id'] = ['institution_class_id'];
        $this->dependency['institution_class_id'] = ['competency_template_id'];
        $this->dependency['competency_template_id'] = ['competency_period_id'];
        $this->dependency['competency_period_id'] = ['competency_item_id'];
        $this->dependency['competency_item_id'] = ['select_file'];
        //POCOR-9584: end

        //POCOR-9584: start - academic_period_id and institution_class_id visible from start
        $this->ControllerAction->field('academic_period_id', ['type' => 'select', 'visible' => true]);
        $this->ControllerAction->field('institution_class_id', ['type' => 'select', 'visible' => true]);
        $this->ControllerAction->field('competency_template_id', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('competency_period_id', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('competency_item_id', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['academic_period_id', 'institution_class_id', 'competency_template_id', 'competency_period_id', 'competency_item_id', 'select_file']);
        //POCOR-9584: end

        $currentFieldName = strtolower(str_replace(['change', '_', 'id'], '', $entity->submit ?? ''));

        if (isset($this->request->getData()[$alias])) {
            $unsetFlag = false;
            $aryRequestData = $this->request->getData()[$alias];
            foreach ($aryRequestData as $requestData => $value) {
                $query = $this->request->getQueryParams();
                $data = $this->request->getData();

                if ($unsetFlag) {
                    unset($query[$requestData]);
                    $data[$alias][$requestData] = 0;
                }

                if ($currentFieldName == str_replace('_', '', $requestData)) {
                    $unsetFlag = true;
                }

                $this->request = $this->request->withQueryParams($query);
                $this->request = $this->request->withParsedBody($data);
            }

            $aryRequestData = $this->request->getData()[$alias];
            foreach ($aryRequestData as $requestData => $value) {
                if (isset($this->dependency[$requestData]) && $value) {
                    $aryDependencies = $this->dependency[$requestData];
                    foreach ($aryDependencies as $dependency) {
                        //POCOR-9584: start - merge POST data into existing query params (was withQueryParams($requestDataArray) which lost URL params)
                        $requestDataArray = $this->request->getData()[$alias];
                        $this->request = $this->request->withQueryParams(
                            array_merge($this->request->getQueryParams(), $requestDataArray)
                        );
                        //POCOR-9584: end
                        $this->ControllerAction->field($dependency, ['visible' => true]);
                    }
                }
            }
        }
    }

    //POCOR-9584: start - renamed onUpdateFieldAcademicPeriod → onUpdateFieldAcademicPeriodId
    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            $attr['onChangeReload'] = 'changeAcademicPeriodId';
        }
        return $attr;
    }
    //POCOR-9584: end

    public function addEditOnChangeAcademicPeriodId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        //POCOR-9584: start - clean addEditOnChange* handler for academic_period_id
        $alias = $this->getAlias();
        if ($this->request->is(['post', 'put'])) {
            if (isset($this->request->getData()[$alias]['academic_period_id'])) {
                $value = $this->request->getData()[$alias]['academic_period_id'];
                $this->request = $this->request->withQueryParams(
                    array_merge($this->request->getQueryParams(), ['academic_period_id' => $value])
                );
            }
        }
        //POCOR-9584: end
    }

    //POCOR-9584: start - commented out dead addEditOnChangeAcademicPeriod (CakePHP3 mutable mutation, broken in CakePHP5)
    /*
    public function addEditOnChangeAcademicPeriod(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('academic_period', $request->getData()[$this->getAlias()])) {
                    $requestData = $request->getData();
                    $requestData[$this->getAlias()]['period'] = $requestData[$this->getAlias()]['academic_period'];
                    $this->request = $this->request->withParsedBody($requestData);
                }
            }
        }
    }
    */
    //POCOR-9584: end

    //POCOR-9584: start - commented out dead onUpdateFieldClassBAK (unused backup, old API)
    /*
    public function onUpdateFieldClassBAK(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        // ... dead code ...
    }
    */
    //POCOR-9584: end

    //POCOR-9584: start - renamed onUpdateFieldClass → onUpdateFieldInstitutionClassId
    public function onUpdateFieldInstitutionClassId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $alias = $this->getAlias();
            $academicPeriodId = $request->getData($alias . '.academic_period_id')
                ?? $this->request->getQuery('academic_period_id')
                ?? $this->AcademicPeriods->getCurrent();
            $institutionId = $this->getInstitutionID();
            $userId = $this->Auth->user('id');
            $AccessControl = $this->AccessControl;
            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $roles = $Institutions->getInstitutionRoles($userId, $institutionId);
            $query = $InstitutionClasses->find();
            if (!$AccessControl->isAdmin()) {
                $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
                $subjectPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
                if (!$classPermission && !$subjectPermission) {
                    $query->where(['1 = 0'], [], true);
                } else {
                    $InstitutionClassesSecondaryStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionClassesSecondaryStaff');
                    $classData = $InstitutionClassesSecondaryStaff->find()
                        ->select([$InstitutionClassesSecondaryStaff->aliasField('institution_class_id')])
                        ->where([$InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id') => $userId])
                        ->toArray();

                    $classIds = [];
                    foreach ($classData as $row) {
                        $classIds[] = $row->institution_class_id;
                    }

                    if (!empty($classIds)) {
                        $query->where([
                            'OR' => [
                                ['InstitutionClasses.id IN' => $classIds],
                                ['InstitutionClasses.staff_id' => $userId],
                            ]
                        ]);
                    } else {
                        $query->where(['InstitutionClasses.staff_id' => $userId]);
                    }
                }
            }

            $classOptions = $query
                ->find('list')
                ->where([
                    $InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                    $InstitutionClasses->aliasField('institution_id') => $institutionId,
                ])
                ->group([$InstitutionClasses->aliasField('id')])
                ->toArray();

            $attr['options'] = $classOptions;
            $attr['onChangeReload'] = 'changeInstitutionClassId';
        }
        return $attr;
    }
    //POCOR-9584: end

    public function addEditOnChangeInstitutionClassId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        //POCOR-9584: start - clean addEditOnChange* handler for institution_class_id
        $alias = $this->getAlias();
        if ($this->request->is(['post', 'put'])) {
            if (isset($this->request->getData()[$alias]['institution_class_id'])) {
                $value = $this->request->getData()[$alias]['institution_class_id'];
                $this->request = $this->request->withQueryParams(
                    array_merge($this->request->getQueryParams(), ['institution_class_id' => $value])
                );
            }
        }
        //POCOR-9584: end
    }

    //POCOR-9584: start - commented out dead addEditOnChangeClass (CakePHP3 mutable mutation, broken in CakePHP5)
    /*
    public function addEditOnChangeClass(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                $requestData = $request->getData()[$this->getAlias()];
                if (isset($requestData['academic_period'])) {
                    $requestData['period'] = $requestData['academic_period'];
                    $request->getData()[$this->getAlias()] = $requestData;
                }
                if (array_key_exists('class', $request->getData()[$this->getAlias()])) {
                    $requestData['class'] = $request->getData()['ImportCompetencyResults']['class'];
                    $request->getData()[$this->getAlias()] = $requestData;
                }
            }
        }
    }
    */
    //POCOR-9584: end

    //POCOR-9584: start - renamed onUpdateFieldCompetencyTemplate → onUpdateFieldCompetencyTemplateId
    public function onUpdateFieldCompetencyTemplateId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $alias = $this->getAlias();
            $academicPeriodId = $request->getData($alias . '.academic_period_id')
                ?? $this->request->getQuery('academic_period_id')
                ?? $this->AcademicPeriods->getCurrent();
            //POCOR-9584: start - institution_class_id (was class); fixed IS operator for non-null value
            $classId = $request->getData($alias . '.institution_class_id');
            //POCOR-9584: end
            $institutionId = $this->getInstitutionID();

            $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
            $educationGrades = [];
            if (!empty($classId)) {
                $educationGrades = $InstitutionClassGrades->find()
                    ->where([$InstitutionClassGrades->aliasField('institution_class_id') => $classId]) //POCOR-9584: removed IS operator (broken in CakePHP5 for non-null)
                    ->extract('education_grade_id')
                    ->toArray();
            }

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

            $attr['options'] = $templateOptions;
            $attr['onChangeReload'] = 'changeCompetencyTemplateId';
        }
        return $attr;
    }
    //POCOR-9584: end

    public function addEditOnChangeCompetencyTemplateId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        //POCOR-9584: start - clean addEditOnChange* handler for competency_template_id
        $alias = $this->getAlias();
        if ($this->request->is(['post', 'put'])) {
            if (isset($this->request->getData()[$alias]['competency_template_id'])) {
                $value = $this->request->getData()[$alias]['competency_template_id'];
                $this->request = $this->request->withQueryParams(
                    array_merge($this->request->getQueryParams(), ['competency_template_id' => $value])
                );
            }
        }
        //POCOR-9584: end
    }

    //POCOR-9584: start - renamed onUpdateFieldCompetencyPeriod → onUpdateFieldCompetencyPeriodId
    public function onUpdateFieldCompetencyPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $alias = $this->getAlias();
            // Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriodId action=' . json_encode($action)); //[TEMP-LOG]
            // Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriodId $this->request->getQuery(academic_period_id)=' . json_encode($this->request->getQuery('academic_period_id'))); //[TEMP-LOG]
            // Log::debug('@ImportCompetencyResults::onUpdateFieldCompetencyPeriodId $this->request->getQuery(competency_template_id)=' . json_encode($this->request->getQuery('competency_template_id'))); //[TEMP-LOG]

            //POCOR-9584: start - renamed academic_period → academic_period_id, competency_template → competency_template_id
            $academicPeriodId = $this->request->getQuery('academic_period_id')
                ?? $this->AcademicPeriods->getCurrent();
            $competencyTemplateId = $this->request->getQuery('competency_template_id');
            //POCOR-9584: end

            $competencyPeriodOptions = [];
            if (!is_null($competencyTemplateId)) {
                $competencyPeriodOptions = $this->CompetencyPeriods
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([
                        $this->CompetencyPeriods->aliasField('academic_period_id') => $academicPeriodId,
                        $this->CompetencyPeriods->aliasField('competency_template_id') => $competencyTemplateId
                    ])
                    ->toArray();
            }

            $attr['options'] = $competencyPeriodOptions;
            $attr['onChangeReload'] = 'changeCompetencyPeriodId';
        }
        return $attr;
    }
    //POCOR-9584: end

    public function addEditOnChangeCompetencyPeriodId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        //POCOR-9584: start - clean addEditOnChange* handler for competency_period_id
        $alias = $this->getAlias();
        if ($this->request->is(['post', 'put'])) {
            if (isset($this->request->getData()[$alias]['competency_period_id'])) {
                $value = $this->request->getData()[$alias]['competency_period_id'];
                $this->request = $this->request->withQueryParams(
                    array_merge($this->request->getQueryParams(), ['competency_period_id' => $value])
                );
            }
        }
        //POCOR-9584: end
    }

    //POCOR-9584: start - renamed onUpdateFieldCompetencyItem → onUpdateFieldCompetencyItemId
    public function onUpdateFieldCompetencyItemId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $alias = $this->getAlias();
            $competencyItemsPeriodsTable = TableRegistry::getTableLocator()->get('Competency.CompetencyItemsPeriods');
            $competencyCriteriasTable = TableRegistry::getTableLocator()->get('Competency.CompetencyCriterias');
            $conditions = [];
            //POCOR-9584: start - renamed field keys to _id suffix
            if (!empty($request->getData()[$alias]['academic_period_id']) && !empty($request->getData()[$alias]['competency_template_id']) && !empty($request->getData()[$alias]['competency_period_id'])) {
                $conditions[] = [
                    $competencyItemsPeriodsTable->aliasField('academic_period_id') => $request->getData()[$alias]['academic_period_id'],
                    $competencyItemsPeriodsTable->aliasField('competency_template_id') => $request->getData()[$alias]['competency_template_id'],
                    $competencyItemsPeriodsTable->aliasField('competency_period_id') => $request->getData()[$alias]['competency_period_id']
                ];
            }
            //POCOR-9584: end

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
            $attr['onChangeReload'] = 'changeCompetencyItemId';
        }
        return $attr;
    }
    //POCOR-9584: end

    public function addEditOnChangeCompetencyItemId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        //POCOR-9584: start - clean addEditOnChange* handler for competency_item_id
        $alias = $this->getAlias();
        if ($this->request->is(['post', 'put'])) {
            if (isset($this->request->getData()[$alias]['competency_item_id'])) {
                $value = $this->request->getData()[$alias]['competency_item_id'];
                $this->request = $this->request->withQueryParams(
                    array_merge($this->request->getQueryParams(), ['competency_item_id' => $value])
                );
            }
        }
        //POCOR-9584: end
    }

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $alias = $this->getAlias();
        $requestData = $this->request->getData()[$alias];
        //POCOR-9584: start - renamed all field keys to DB column names
        $tempRow['academic_period_id'] = $requestData['academic_period_id'];
        $tempRow['competency_template_id'] = $requestData['competency_template_id'];
        $tempRow['competency_period_id'] = $requestData['competency_period_id'];
        $tempRow['competency_item_id'] = $requestData['competency_item_id'];
        $tempRow['institution_id'] = $this->getInstitutionID();
        //POCOR-9584: end

        return true;
    }

    public function getStudentArray()
    {
        //POCOR-9584: start - renamed class → institution_class_id; kept class_id fallback for pass[1] encoded
        //   On POST (file upload), addOnInitialize clears query params before addBeforeSave runs,
        //   so getQuery() returns null; fall back to POST data as second priority.
        $qs = $this->getQueryString();
        $alias = $this->getAlias();
        $postData = $this->request->getData()[$alias] ?? [];
        $classId = $this->request->getQuery('institution_class_id')
            ?? ($postData['institution_class_id'] ?? null)
            ?? ($qs['class_id'] ?? null);
        //POCOR-9584: end
        // Log::debug('@ImportCompetencyResultsTable::getStudentArray getQuery=' . json_encode($this->request->getQuery('institution_class_id')) . ' postData=' . json_encode($postData) . ' classId=' . json_encode($classId)); //[TEMP-LOG]

        if (empty($classId)) {
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
        //POCOR-9584: start - renamed all query param keys to DB column names with _id suffix
        //   On POST (file upload), addOnInitialize clears query params before addBeforeSave runs,
        //   so getQuery() returns null; fall back to POST data as second priority.
        $qs = $this->getQueryString();
        $alias = $this->getAlias();
        $postData = $this->request->getData()[$alias] ?? [];
        $academicPeriod    = $this->request->getQuery('academic_period_id')
            ?? ($postData['academic_period_id'] ?? null)
            ?? ($qs['academic_period_id'] ?? null);
        $competencyItem    = $this->request->getQuery('competency_item_id')
            ?? ($postData['competency_item_id'] ?? null)
            ?? ($qs['competency_item_id'] ?? null);
        $competencyTemplate = $this->request->getQuery('competency_template_id')
            ?? ($postData['competency_template_id'] ?? null)
            ?? ($qs['competency_template_id'] ?? null);
        //POCOR-9584: end
        // Log::debug('@ImportCompetencyResultsTable::getCompetencyCriteriasArray getQuery_academic=' . json_encode($this->request->getQuery('academic_period_id')) . ' post_academic=' . json_encode($postData['academic_period_id'] ?? null) . ' resolved=' . json_encode($academicPeriod)); //[TEMP-LOG]
        // Log::debug('@ImportCompetencyResultsTable::getCompetencyCriteriasArray getQuery_template=' . json_encode($this->request->getQuery('competency_template_id')) . ' post_template=' . json_encode($postData['competency_template_id'] ?? null) . ' resolved=' . json_encode($competencyTemplate)); //[TEMP-LOG]
        // Log::debug('@ImportCompetencyResultsTable::getCompetencyCriteriasArray getQuery_item=' . json_encode($this->request->getQuery('competency_item_id')) . ' post_item=' . json_encode($postData['competency_item_id'] ?? null) . ' resolved=' . json_encode($competencyItem)); //[TEMP-LOG]

        if (empty($academicPeriod) || empty($competencyTemplate)) {
            return [];
        }

        $competencyCriteriasTable = TableRegistry::getTableLocator()->get('Competency.CompetencyCriterias');
        $arrayCompetencyCriterias = $competencyCriteriasTable->find()
            ->where([
                $competencyCriteriasTable->aliasField('academic_period_id') => $academicPeriod,
                $competencyCriteriasTable->aliasField('competency_template_id') => $competencyTemplate
            ])
            ->toArray();

        if (!empty($competencyItem)) {
            $arrayCompetencyCriterias = array_filter(
                $arrayCompetencyCriterias,
                fn($row) => $row->competency_item_id == $competencyItem
            );
            $arrayCompetencyCriterias = array_values($arrayCompetencyCriterias);
        }

        // Log::debug('@ImportCompetencyResultsTable::getCompetencyCriteriasArray criteriaCount=' . count($arrayCompetencyCriterias) . ' criteriaIds=' . json_encode(array_column($arrayCompetencyCriterias, 'id'))); //[TEMP-LOG]

        return $arrayCompetencyCriterias;
    }
}

