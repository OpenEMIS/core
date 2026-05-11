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
use Cake\Datasource\ConnectionManager;

class ImportOutcomeResultsTable extends AppTable
{
    public function initialize(array $config): void
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        //// Log::debug('@ImportOutcomeResultsTable::initialize START config=' . json_encode($config)); //[TEMP-LOG]
        //POCOR-9584: end

        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.ImportOutcomeResult', [
            'plugin' => 'Institution',
            'model' => 'InstitutionOutcomeResults',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentOutcomes']
        ]);
        $this->addBehavior('Institution.InstitutionTab'); //POCOR-9584: provides getInstitutionID() (mirrors ImportCompetencyResultsTable)

        // register table once
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $this->InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $this->InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $this->EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $this->StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $this->EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
        $this->OutcomeTemplates = TableRegistry::getTableLocator()->get('Outcome.OutcomeTemplates');
        $this->OutcomePeriods = TableRegistry::getTableLocator()->get('Outcome.OutcomePeriods');
        $this->OutcomeCriterias = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
        $this->OutcomeGradingTypes = TableRegistry::getTableLocator()->get('Outcome.OutcomeGradingTypes');

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        //// Log::debug('@ImportOutcomeResultsTable::initialize END tables registered OK'); //[TEMP-LOG]
        //POCOR-9584: end
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
        //POCOR-9584: start - renamed field keys to new _id suffixed names
        return $validator
            ->notEmpty(['academic_period_id', 'institution_class_id', 'education_subject_id', 'outcome_template_id', 'outcome_period_id', 'select_file']);
        //POCOR-9584: end
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        $request = $this->request;
        //POCOR-9584: start - renamed field key education_subject → education_subject_id
        if (empty($request->getData()['ImportOutcomeResults']['education_subject_id'])) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
        //POCOR-9584: end
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        //// Log::debug('@ImportOutcomeResultsTable::addOnInitialize START entity=' . json_encode($entity->toArray())); //[TEMP-LOG]
        //// Log::debug('@ImportOutcomeResultsTable::addOnInitialize queryBefore=' . json_encode($this->request->getQueryParams())); //[TEMP-LOG]
        //// Log::debug('@ImportOutcomeResultsTable::addOnInitialize postData=' . json_encode($this->request->getData())); //[TEMP-LOG]
        //// Log::debug('@ImportOutcomeResultsTable::addOnInitialize routeParams=' . json_encode($this->request->getAttribute('params'))); //[TEMP-LOG]
        //POCOR-9584: end

        $request = $this->request;
        $query = $request->getQuery(); // Get the query parameters

        //POCOR-9584: start - clear new _id suffixed keys only; do NOT clear academic_period_id
        unset($query['institution_class_id']);
        unset($query['outcome_template_id']);
        unset($query['outcome_period_id']);
        unset($query['education_subject_id']);
        //POCOR-9584: end

        // Set the modified query parameters back to the request
        $request = $request->withQueryParams($query);
        $this->request = $request;

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        //// Log::debug('@ImportOutcomeResultsTable::addOnInitialize END queryAfter=' . json_encode($this->request->getQueryParams())); //[TEMP-LOG]
        //POCOR-9584: end
    }


    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        //// Log::debug('@ImportOutcomeResultsTable::addAfterAction START entity_submit=' . json_encode($entity->submit)); //[TEMP-LOG]
        //// Log::debug('@ImportOutcomeResultsTable::addAfterAction postData=' . json_encode($this->request->getData())); //[TEMP-LOG]
        //// Log::debug('@ImportOutcomeResultsTable::addAfterAction queryParams=' . json_encode($this->request->getQueryParams())); //[TEMP-LOG]
        //// Log::debug('@ImportOutcomeResultsTable::addAfterAction alias=' . $this->getAlias()); //[TEMP-LOG]
        //POCOR-9584: end

        //POCOR-9584: start - renamed dependency map keys to new _id suffixed field names
        $this->dependency = [];
        $this->dependency["academic_period_id"] = ["institution_class_id"];
        $this->dependency["institution_class_id"] = ["outcome_template_id"];
        $this->dependency["outcome_template_id"] = ["outcome_period_id"];
        $this->dependency["outcome_period_id"] = ["education_subject_id"];
        $this->dependency["education_subject_id"] = ["select_file"];
        //POCOR-9584: end

        //POCOR-9584: start - renamed fields to new _id suffixed names
        $this->ControllerAction->field('academic_period_id', ['type' => 'select']);
        $this->ControllerAction->field('institution_class_id', ['type' => 'select']);
        $this->ControllerAction->field('outcome_template_id', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('outcome_period_id', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('education_subject_id', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['academic_period_id', 'institution_class_id', 'outcome_template_id', 'outcome_period_id', 'education_subject_id', 'select_file']);
        //POCOR-9584: end

        //Assumptiopn - onChangeReload must be named in this format: change<field_name>. E.g changeClass
        $currentFieldName = strtolower(str_replace("change", "", $entity->submit));

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        //// Log::debug('@ImportOutcomeResultsTable::addAfterAction currentFieldName=' . json_encode($currentFieldName)); //[TEMP-LOG]
        //POCOR-9584: end

        if (isset($this->request->getData()[$this->getAlias()])) {
            $unsetFlag = false;
            $aryRequestData = $this->request->getData()[$this->getAlias()];

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            //// Log::debug('@ImportOutcomeResultsTable::addAfterAction aryRequestData=' . json_encode($aryRequestData)); //[TEMP-LOG]
            //POCOR-9584: end

            //POCOR-9584: start - CakePHP5 immutable request pattern (was mutating getQuery property — invalid in CakePHP5)
            foreach ($aryRequestData as $requestData => $value) {
                $query = $this->request->getQuery();
                $data  = $this->request->getData();
                if ($unsetFlag) {
                    unset($query[$requestData]);
                    $data[$this->getAlias()][$requestData] = 0;
                }
                if ($currentFieldName == str_replace('_', '', $requestData)) {
                    $unsetFlag = true;
                }
                $this->request = $this->request->withQueryParams($query);
                $this->request = $this->request->withParsedBody($data);
            }
            //POCOR-9584: end

            $aryRequestData = $this->request->getData()[$this->getAlias()];
            foreach ($aryRequestData as $requestData => $value) {
                if (isset($this->dependency[$requestData]) && $value) {
                    $aryDependencies = $this->dependency[$requestData];
                    foreach ($aryDependencies as $dependency) {
                        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
                        //// Log::debug('@ImportOutcomeResultsTable::addAfterAction making visible dependency=' . $dependency . ' because requestData=' . $requestData . ' value=' . json_encode($value)); //[TEMP-LOG]
                        //POCOR-9584: end
                        //POCOR-9584: start - merge POST data into existing URL params (preserves academic_period_id etc.)
                        $requestDataArray = $this->request->getData()[$this->getAlias()];
                        $this->request = $this->request->withQueryParams(
                            array_merge($this->request->getQueryParams(), $requestDataArray)
                        );
                        //POCOR-9584: end
                        $this->ControllerAction->field($dependency, ['visible' => true]);
                    }
                }
            }
        } else {
            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            //// Log::debug('@ImportOutcomeResultsTable::addAfterAction no postData for alias ' . $this->getAlias() . ' - skipping dependency loop'); //[TEMP-LOG]
            //POCOR-9584: end
        }

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        //// Log::debug('@ImportOutcomeResultsTable::addAfterAction END'); //[TEMP-LOG]
        //POCOR-9584: end
    }

    //POCOR-9584: start - renamed onUpdateFieldEducationSubject → onUpdateFieldEducationSubjectId
    public function onUpdateFieldEducationSubjectId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        //// Log::debug('@ImportOutcomeResultsTable::onUpdateFieldEducationSubjectId action=' . $action); //[TEMP-LOG]
        //POCOR-9584: end
        if ($action == 'add') {
            $alias = $this->getAlias();
            //POCOR-9584: start - use getData($alias . '.field') pattern; renamed field keys
            $academicPeriodId = $request->getData($alias . '.academic_period_id') ?? $this->AcademicPeriods->getCurrent();
            $outcomeTemplate = $request->getData($alias . '.outcome_template_id') ?? null;
            //POCOR-9584: end

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldEducationSubjectId academicPeriodId=' . json_encode($academicPeriodId) . ' outcomeTemplate=' . json_encode($outcomeTemplate)); //[TEMP-LOG]
            //POCOR-9584: end

            $conditions = [];
            if (!empty($academicPeriodId) && !empty($outcomeTemplate)) {
                $conditions[] =
                [
                    $this->OutcomeCriterias->aliasField('academic_period_id') => $academicPeriodId,
                    $this->OutcomeCriterias->aliasField('outcome_template_id') => $outcomeTemplate
                ];
            }
            $userId = $this->Auth->user('id');
            $AccessControl = $this->AccessControl;
            //POCOR-9584: start - renamed field key class → institution_class_id; use getQuery for URL param
            $data = $request->getData($alias) ?? [];
            $classId = $this->request->getQuery('institution_class_id') ?? ($data['institution_class_id'] ?? null);
            //POCOR-9584: end
            //POCOR-9584: start - guard: no class selected yet → return empty options (avoids null IS error in CakePHP5)
            if (empty($classId)) {
                $attr['options'] = [];
                return $attr;
            }
            //POCOR-9584: end
            $OutcomeCriterias = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
            $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
            $allowedEducationSubjectList = $InstitutionSubjects
             ->find('list', [
                    'keyField' => 'education_subject_id',
                    'valueField' => 'educationSubjects'
                ])
// POCOR-7977               ->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->controller])
                ->select(['educationSubjects' => 'EducationSubjects.name', 'education_subject_id' => 'EducationSubjects.id'])
                ->contain(['EducationSubjects'])
                ->matching('ClassSubjects', function ($q) use ($classId) {
                    return $q->where(['ClassSubjects.institution_class_id' => $classId]);
                })
                ->innerJoin([$OutcomeCriterias->getAlias() => $OutcomeCriterias->getTable()], [
                             $OutcomeCriterias->aliasField('education_grade_id = ') . $InstitutionSubjects->aliasField('education_grade_id'),
                             $OutcomeCriterias->aliasField('education_subject_id = ') . $InstitutionSubjects->aliasField('education_subject_id'),

                            ])
                ->where($conditions)//POCOR-7506
                ->group([
                    'EducationSubjects.id',
                ])->toArray();
                //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
                // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldEducationSubjectId allowedEducationSubjectList count=' . count($allowedEducationSubjectList)); //[TEMP-LOG]
                //POCOR-9584: end
                $attr['options'] = $allowedEducationSubjectList;
                //POCOR-9584: start - renamed onChangeReload value changeEducationGrade → changeEducationSubjectId
                $attr['onChangeReload'] = 'changeEducationSubjectId';
                //POCOR-9584: end
        }
        return $attr;
    }
    //POCOR-9584: end

    //POCOR-9584: start - renamed onUpdateFieldAcademicPeriod → onUpdateFieldAcademicPeriodId
    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldAcademicPeriodId action=' . $action); //[TEMP-LOG]
        //POCOR-9584: end
        if ($action == 'add') {
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            //POCOR-9584: start - renamed onChangeReload value changeAcademicPeriod → changeAcademicPeriodId
            $attr['onChangeReload'] = 'changeAcademicPeriodId';
            //POCOR-9584: end
            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldAcademicPeriodId default=' . json_encode($attr['default']) . ' optionsCount=' . count($attr['options'])); //[TEMP-LOG]
            //POCOR-9584: end
        }
        return $attr;
    }
    //POCOR-9584: end

    //POCOR-9584: start - renamed onUpdateFieldClass → onUpdateFieldInstitutionClassId
    public function onUpdateFieldInstitutionClassId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldInstitutionClassId action=' . $action); //[TEMP-LOG]
        //POCOR-9584: end
        if ($action == 'add') {
            $alias = $this->getAlias();
            //POCOR-9584: start - use getData($alias . '.field') pattern; renamed field key academic_period → academic_period_id
            $academicPeriodId = $request->getData($alias . '.academic_period_id') ?? $this->AcademicPeriods->getCurrent();
            //POCOR-9584: end
            $institutionId = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldInstitutionClassId institutionId=' . json_encode($institutionId) . ' academicPeriodId=' . json_encode($academicPeriodId)); //[TEMP-LOG]
            //POCOR-9584: end

            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
            $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
            $classNameOption = $InstitutionClasses->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
                ->leftJoin([$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()], [
                    $InstitutionClassGrades->aliasField('institution_class_id = ') . $InstitutionClasses->aliasField('id')
                ])
                ->leftJoin([$EducationGrades->getAlias() => $EducationGrades->getTable()], [
                    $EducationGrades->aliasField('id = ') . $InstitutionClassGrades->aliasField('education_grade_id')
                ])
                ->leftJoin([$this->OutcomeTemplates->getAlias() => $this->OutcomeTemplates->getTable()], [
                    $this->OutcomeTemplates->aliasField('education_grade_id = ') . $EducationGrades->aliasField('id')
                ])
                ->where([
                    $InstitutionClasses->aliasField('institution_id') => $institutionId,
                    $InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                    $this->OutcomeTemplates->aliasField('academic_period_id') => $academicPeriodId
                ])
                ->toArray();

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldInstitutionClassId classNameOption count=' . count($classNameOption)); //[TEMP-LOG]
            //POCOR-9584: end

            $attr['options'] = $classNameOption;
            //POCOR-9584: start - renamed onChangeReload value changeClass → changeInstitutionClassId
            $attr['onChangeReload'] = 'changeInstitutionClassId';
            //POCOR-9584: end
            return $attr;
        }
// POCOR-7977 end

    }
    //POCOR-9584: end


    /*
    //POCOR-9584: start - commented out dead backup method onUpdateFieldClassBkp
    public function onUpdateFieldClassBkp(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->getData('ImportOutcomeResults')['academic_period']) ? $request->getData('ImportOutcomeResults')['academic_period'] : $this->AcademicPeriods->getCurrent();
            $institutionId = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk

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
                        $query->innerJoin(['ClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                            'OR' => [
                                'ClassesSecondaryStaff.institution_class_id = InstitutionClasses.id',
                                'ClassesSecondaryStaff.secondary_staff_id = InstitutionClasses.staff_id',
                            ]
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
                                        ['InstitutionSubjectStaff.staff_id' => $userId],
                                        ['InstitutionSubjectStaff.institution_id' => $institutionId] //POCOR-7506
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
    //POCOR-9584: end
    */

    //POCOR-9584: start - renamed onUpdateFieldOutcomeTemplate → onUpdateFieldOutcomeTemplateId
    public function onUpdateFieldOutcomeTemplateId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomeTemplateId action=' . $action); //[TEMP-LOG]
        //POCOR-9584: end
        if ($action == 'add') {
            $alias = $this->getAlias();
            //POCOR-9584: start - use getData($alias . '.field') pattern; renamed field keys
            $academicPeriodId = $request->getData($alias . '.academic_period_id') ?? $this->AcademicPeriods->getCurrent();
            //POCOR-9584: end
            //POCOR-9584: start - renamed field key class → institution_class_id
            $classId = $request->getQuery('institution_class_id') ?? $this->request->getQuery('institution_class_id');
            //POCOR-9584: end
            $institutionId = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk
            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomeTemplateId academicPeriodId=' . json_encode($academicPeriodId) . ' classId=' . json_encode($classId) . ' institutionId=' . json_encode($institutionId)); //[TEMP-LOG]
            //POCOR-9584: end
            // if class id is not null, then filter Outcome Template by class_grades of the class else by institution_grades of the school
            if (!is_null($classId) && !empty($classId)) {
                $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
                $educationGrades = $InstitutionClassGrades->find()
                    ->where([$InstitutionClassGrades->aliasField('institution_class_id') => $classId])
                    ->extract('education_grade_id')
                    ->toArray();
            } else {
                $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
                $educationGrades = $InstitutionGrades->find()
                    ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId]) //POCOR-9584: IS → = for non-null
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

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomeTemplateId templateOptions count=' . count($templateOptions)); //[TEMP-LOG]
            //POCOR-9584: end
            $attr['options'] = $templateOptions;
            //POCOR-9584: start - renamed onChangeReload value changeOutcomeTemplate → changeOutcomeTemplateId
            $attr['onChangeReload'] = 'changeOutcomeTemplateId';
            //POCOR-9584: end
        }
        return $attr;
    }
    //POCOR-9584: end

    //POCOR-9584: start - renamed onUpdateFieldOutcomePeriod → onUpdateFieldOutcomePeriodId
    public function onUpdateFieldOutcomePeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomePeriodId action=' . $action); //[TEMP-LOG]
        //POCOR-9584: end
        if ($action == 'add') {
            $alias = $this->getAlias();
            //POCOR-9584: start - use getData($alias . '.field') pattern; renamed field keys; fix IS operator bug
            $academicPeriodId = $request->getData($alias . '.academic_period_id') ?? $this->AcademicPeriods->getCurrent();
            $outcomeTemplateId = $request->getData($alias . '.outcome_template_id') ?? null;
            //POCOR-9584: end

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomePeriodId academicPeriodId=' . json_encode($academicPeriodId) . ' outcomeTemplateId=' . json_encode($outcomeTemplateId)); //[TEMP-LOG]
            //POCOR-9584: end

            $outcomePeriodOptions = [];
            //POCOR-9584: start - use renamed field key outcome_template_id; fix IS operator bug in where clause
            if (!is_null($outcomeTemplateId)) {
                $outcomePeriodOptions = $this->OutcomePeriods
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([
                        $this->OutcomePeriods->aliasField('academic_period_id') => $academicPeriodId,
                        $this->OutcomePeriods->aliasField('outcome_template_id') => $outcomeTemplateId
                    ])
                    ->toArray();
            }
            //POCOR-9584: end

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            // Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomePeriodId outcomePeriodOptions count=' . count($outcomePeriodOptions)); //[TEMP-LOG]
            //POCOR-9584: end
            $attr['options'] = $outcomePeriodOptions;
            //POCOR-9584: start - renamed onChangeReload value changeOutcomePeriod → changeOutcomePeriodId
            $attr['onChangeReload'] = 'changeOutcomePeriodId';
            //POCOR-9584: end
        }
        return $attr;
    }
    //POCOR-9584: end

    //POCOR-9584: start - addEditOnChange handlers for each field (mirrors ImportAssessmentItemResultsTable pattern)
    public function addEditOnChangeAcademicPeriodId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->request = $this->request->withQueryParams(array_merge($this->request->getQueryParams(), ['academic_period_id' => $data[$this->getAlias()]['academic_period_id']]));
    }

    public function addEditOnChangeInstitutionClassId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->request = $this->request->withQueryParams(array_merge($this->request->getQueryParams(), ['institution_class_id' => $data[$this->getAlias()]['institution_class_id']]));
    }

    public function addEditOnChangeOutcomeTemplateId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->request = $this->request->withQueryParams(array_merge($this->request->getQueryParams(), ['outcome_template_id' => $data[$this->getAlias()]['outcome_template_id']]));
    }

    public function addEditOnChangeOutcomePeriodId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->request = $this->request->withQueryParams(array_merge($this->request->getQueryParams(), ['outcome_period_id' => $data[$this->getAlias()]['outcome_period_id']]));
    }

    public function addEditOnChangeEducationSubjectId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->request = $this->request->withQueryParams(array_merge($this->request->getQueryParams(), ['education_subject_id' => $data[$this->getAlias()]['education_subject_id']]));
    }
    //POCOR-9584: end

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        // Log::debug('@ImportOutcomeResultsTable::onImportModelSpecificValidation START tempRow=' . json_encode($tempRow->getArrayCopy())); //[TEMP-LOG]
        //POCOR-9584: end

        $requestData = $this->request->getData()[$this->getAlias()];
        //POCOR-9584: start - renamed requestData keys to new _id suffixed field names
        $tempRow['academic_period_id'] = $requestData['academic_period_id'];
        $tempRow['outcome_template_id'] = $requestData['outcome_template_id'];
        $tempRow['outcome_period_id'] = $requestData['outcome_period_id'];
        $tempRow['institution_class_id'] = $requestData['institution_class_id'];
        $tempRow['education_subject_id'] = $requestData['education_subject_id'];
        //POCOR-9584: end
        $tempRow['institution_id'] = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        // Log::debug('@ImportOutcomeResultsTable::onImportModelSpecificValidation requestData=' . json_encode($requestData)); //[TEMP-LOG]
        // Log::debug('@ImportOutcomeResultsTable::onImportModelSpecificValidation institution_id=' . json_encode($tempRow['institution_id']) . ' outcome_criteria_id=' . json_encode($tempRow['outcome_criteria_id'] ?? 'not_set')); //[TEMP-LOG]
        //POCOR-9584: end

        $outcomeCriteriaEntity = $this->OutcomeCriterias->find()
            ->matching('Templates')
            ->contain('OutcomeGradingTypes.GradingOptions')
            ->where([
                $this->OutcomeCriterias->aliasField('id') => $tempRow['outcome_criteria_id'],
                $this->OutcomeCriterias->aliasField('outcome_template_id') => $tempRow['outcome_template_id'],
                $this->OutcomeCriterias->aliasField('academic_period_id') => $tempRow['academic_period_id']
            ])
            ->first();

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        // Log::debug('@ImportOutcomeResultsTable::onImportModelSpecificValidation outcomeCriteriaEntity=' . json_encode($outcomeCriteriaEntity ? $outcomeCriteriaEntity->toArray() : null)); //[TEMP-LOG]
        //POCOR-9584: end

            $tempRow['education_subject_id'] = $outcomeCriteriaEntity->education_subject_id;
            $tempRow['education_grade_id'] = $outcomeCriteriaEntity->_matchingData['Templates']->education_grade_id;

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        // Log::debug('@ImportOutcomeResultsTable::onImportModelSpecificValidation END education_subject_id=' . json_encode($tempRow['education_subject_id']) . ' education_grade_id=' . json_encode($tempRow['education_grade_id'])); //[TEMP-LOG]
        //POCOR-9584: end

        return true;
    }
}
