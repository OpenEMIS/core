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
use Cake\Datasource\ConnectionManager;

class ImportOutcomeResultsTable extends AppTable
{
    public function initialize(array $config): void
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::initialize START config=' . json_encode($config)); //[TEMP-LOG]
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
        Log::debug('@ImportOutcomeResultsTable::initialize END tables registered OK'); //[TEMP-LOG]
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
        return $validator
            ->notEmpty(['academic_period','class' ,'education_subject', 'outcome_template', 'outcome_period', 'select_file']);
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        $request = $this->request;
        if (empty($request->getData()['ImportOutcomeResults']['education_subject'])) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

   public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::addOnInitialize START entity=' . json_encode($entity->toArray())); //[TEMP-LOG]
        Log::debug('@ImportOutcomeResultsTable::addOnInitialize queryBefore=' . json_encode($this->request->getQueryParams())); //[TEMP-LOG]
        Log::debug('@ImportOutcomeResultsTable::addOnInitialize postData=' . json_encode($this->request->getData())); //[TEMP-LOG]
        Log::debug('@ImportOutcomeResultsTable::addOnInitialize routeParams=' . json_encode($this->request->getAttribute('params'))); //[TEMP-LOG]
        //POCOR-9584: end

        $request = $this->request;
        $query = $request->getQuery(); // Get the query parameters

        // Unset specific query parameters
        unset($query['period']);
        unset($query['class']);
        unset($query['education_subject']);
        unset($query['outcome_template']);
        unset($query['outcome_period']);

        // Set the modified query parameters back to the request
        $request = $request->withQueryParams($query);
        $this->request = $request;

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::addOnInitialize END queryAfter=' . json_encode($this->request->getQueryParams())); //[TEMP-LOG]
        //POCOR-9584: end
    }


    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::addAfterAction START entity_submit=' . json_encode($entity->submit)); //[TEMP-LOG]
        Log::debug('@ImportOutcomeResultsTable::addAfterAction postData=' . json_encode($this->request->getData())); //[TEMP-LOG]
        Log::debug('@ImportOutcomeResultsTable::addAfterAction queryParams=' . json_encode($this->request->getQueryParams())); //[TEMP-LOG]
        Log::debug('@ImportOutcomeResultsTable::addAfterAction alias=' . $this->getAlias()); //[TEMP-LOG]
        //POCOR-9584: end

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

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::addAfterAction currentFieldName=' . json_encode($currentFieldName)); //[TEMP-LOG]
        //POCOR-9584: end

        if (isset($this->request->getData()[$this->getAlias()])) {
            $unsetFlag = false;
            $aryRequestData = $this->request->getData()[$this->getAlias()];

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            Log::debug('@ImportOutcomeResultsTable::addAfterAction aryRequestData=' . json_encode($aryRequestData)); //[TEMP-LOG]
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
                        Log::debug('@ImportOutcomeResultsTable::addAfterAction making visible dependency=' . $dependency . ' because requestData=' . $requestData . ' value=' . json_encode($value)); //[TEMP-LOG]
                        //POCOR-9584: end
                        //POCOR-9584: start - was $this->request->getQuery = ... (invalid property mutation); use withQueryParams instead
                        $requestDataArray = $this->request->getData()[$this->getAlias()];
                        $this->request = $this->request->withQueryParams($requestDataArray);
                        //POCOR-9584: end
                        $this->ControllerAction->field($dependency, ['visible' => true]);
                    }
                }
            }
        } else {
            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            Log::debug('@ImportOutcomeResultsTable::addAfterAction no postData for alias ' . $this->getAlias() . ' - skipping dependency loop'); //[TEMP-LOG]
            //POCOR-9584: end
        }

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::addAfterAction END'); //[TEMP-LOG]
        //POCOR-9584: end
    }

    public function onUpdateFieldEducationSubject(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::onUpdateFieldEducationSubject action=' . $action); //[TEMP-LOG]
        //POCOR-9584: end
        if ($action == 'add') {
            $data = $request->getData('ImportOutcomeResults');
            $academicPeriodId = $data['academic_period'] ?? $this->AcademicPeriods->getCurrent();
            $outcomeTemplate = $data['outcome_template'] ?? null;

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            Log::debug('@ImportOutcomeResultsTable::onUpdateFieldEducationSubject academicPeriodId=' . json_encode($academicPeriodId) . ' outcomeTemplate=' . json_encode($outcomeTemplate)); //[TEMP-LOG]
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
            //POCOR-9584: start - was falling back to 'default_value' (no DB match); use POST data as fallback instead
            $classId = $this->request->getQuery('class') ?? ($data['class'] ?? null);
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
                Log::debug('@ImportOutcomeResultsTable::onUpdateFieldEducationSubject allowedEducationSubjectList count=' . count($allowedEducationSubjectList)); //[TEMP-LOG]
                //POCOR-9584: end
                $attr['options'] = $allowedEducationSubjectList;
                // useing onChangeReload to do visible
                $attr['onChangeReload'] = 'changeEducationGrade';
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriod(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::onUpdateFieldAcademicPeriod action=' . $action); //[TEMP-LOG]
        //POCOR-9584: end
        if ($action == 'add') {
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeAcademicPeriod';
            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            Log::debug('@ImportOutcomeResultsTable::onUpdateFieldAcademicPeriod default=' . json_encode($attr['default']) . ' optionsCount=' . count($attr['options'])); //[TEMP-LOG]
            //POCOR-9584: end
        }
        return $attr;
    }

    public function onUpdateFieldClass(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::onUpdateFieldClass action=' . $action); //[TEMP-LOG]
        //POCOR-9584: end
        if ($action == 'add') {
            $data = $request->getData('ImportOutcomeResults');
            $academicPeriodId = $data['academic_period'] ?? $this->AcademicPeriods->getCurrent();
//            $outcomeTemplate = $data['outcome_template'] ?? null;
//            $classId = $data['class'] ?? 'default_value';
            $institutionId = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk
//            $academicPeriodId = !is_null($request->getData('ImportOutcomeResults')['academic_period']) ? $request->getData('ImportOutcomeResults')['academic_period'] : $this->AcademicPeriods->getCurrent();
//            $institutionId = !empty($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $this->request->getSession()->read('Institution.Institutions.id');
//// POCOR-7977 start
//            $userId = $this->Auth->user('id');
//            $AccessControl = $this->AccessControl;
//            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
//            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
//            $roles = $Institutions->getInstitutionRoles($userId, $institutionId);
//            $query = $InstitutionClasses->find();
//            if (!$AccessControl->isAdmin()) {
//                if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles) && !$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles))
//                 {
//                    $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
//                    $subjectPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
//                    if (!$classPermission && !$subjectPermission) {
//                        $query->where(['1 = 0'], [], true);
//                    } else {
//                        //POCOR-7506 start
//                        $connection = ConnectionManager::get('default');
//                        $statement = $connection->prepare("SELECT subq.institution_classes_id
//                                            ,subq.institution_classes_name
//                                        FROM
//                                        (
//                                            SELECT institution_classes.id institution_classes_id
//                                                ,institution_classes.name institution_classes_name
//                                            FROM institution_classes
//                                            WHERE institution_classes.academic_period_id = $academicPeriodId
//                                            AND institution_classes.staff_id = $userId
//                                            AND institution_classes.institution_id = $institutionId
//
//                                            UNION ALL
//
//                                            SELECT class_info.institution_classes_id
//                                                ,class_info.institution_classes_name
//                                            FROM institution_classes_secondary_staff
//                                            INNER JOIN
//                                            (
//                                                SELECT institution_classes.id institution_classes_id
//                                                    ,institution_classes.name institution_classes_name
//                                                FROM institution_classes
//                                                WHERE institution_classes.academic_period_id = $academicPeriodId
//                                                AND institution_classes.institution_id = $institutionId
//                                            ) class_info
//                                            ON class_info.institution_classes_id = institution_classes_secondary_staff.institution_class_id
//                                            WHERE institution_classes_secondary_staff.secondary_staff_id = $userId
//
//                                            UNION ALL
//
//                                            SELECT subject_info.institution_classes_id
//                                                ,subject_info.institution_classes_name
//                                            FROM institution_subject_staff
//                                            INNER JOIN
//                                            (
//                                                SELECT institution_subjects.id institution_subject_id
//                                                    ,institution_classes.id institution_classes_id
//                                                    ,institution_classes.name institution_classes_name
//                                                FROM institution_subjects
//                                                INNER JOIN institution_class_subjects
//                                                ON institution_class_subjects.institution_subject_id = institution_subjects.id
//                                                INNER JOIN institution_classes
//                                                ON institution_classes.id = institution_class_subjects.institution_class_id
//                                                WHERE institution_subjects.academic_period_id = $academicPeriodId
//                                                AND institution_classes.institution_id = $institutionId
//                                            ) subject_info
//                                            ON subject_info.institution_subject_id = institution_subject_staff.institution_subject_id
//                                            WHERE institution_subject_staff.staff_id = $userId
//                                        ) subq
//
//                                            ");
//                        $statement->execute();
//                        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
//                        $classlist = [];
//                        if(!empty($result)){
//                           foreach($result as $val){
//                            $classlist[$val['institution_classes_id']] = $val['institution_classes_name'];
//                           }
//                        }
//                        $attr['options'] = $classlist;
//                        $attr['onChangeReload'] = 'changeClass';
//                    }
//                    //POCOR-7506 end
//                }
//
//            }else{
//                $classOptions = $query
//                ->find('list')
//                ->where([
//                    $InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
//                    $InstitutionClasses->aliasField('institution_id') => $institutionId])
//                ->group([
//                    $InstitutionClasses->aliasField('id')
//                ])
//                ->toArray();
//                $attr['options'] = $classOptions;
//                $attr['onChangeReload'] = 'changeClass';
//            }

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            Log::debug('@ImportOutcomeResultsTable::onUpdateFieldClass institutionId=' . json_encode($institutionId) . ' academicPeriodId=' . json_encode($academicPeriodId)); //[TEMP-LOG]
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
            Log::debug('@ImportOutcomeResultsTable::onUpdateFieldClass classNameOption count=' . count($classNameOption)); //[TEMP-LOG]
            //POCOR-9584: end

            $attr['options'] = $classNameOption;
            $attr['onChangeReload'] = 'changeClass';
            return $attr;
        }
// POCOR-7977 end

    }


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

    public function onUpdateFieldOutcomeTemplate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomeTemplate action=' . $action); //[TEMP-LOG]
        //POCOR-9584: end
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->getData('ImportOutcomeResults')['academic_period']) ? $request->getData('ImportOutcomeResults')['academic_period'] : $this->AcademicPeriods->getCurrent();
            //POCOR-9584: start - $request arg is the original controller request; fall back to $this->request (updated by addAfterAction withQueryParams)
            $classId = $request->getQuery('class') ?? $this->request->getQuery('class');
            //POCOR-9584: end
            $institutionId = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk
            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomeTemplate academicPeriodId=' . json_encode($academicPeriodId) . ' classId=' . json_encode($classId) . ' institutionId=' . json_encode($institutionId)); //[TEMP-LOG]
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
            Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomeTemplate templateOptions count=' . count($templateOptions)); //[TEMP-LOG]
            //POCOR-9584: end
            $attr['options'] = $templateOptions;
            // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeOutcomeTemplate';
        }
        return $attr;
    }

    public function onUpdateFieldOutcomePeriod(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomePeriod action=' . $action); //[TEMP-LOG]
        //POCOR-9584: end
        if ($action == 'add') {
            $data = $request->getData('ImportOutcomeResults');
            $academicPeriodId = $data['academic_period'] ?? $this->AcademicPeriods->getCurrent();
            $outcomeTemplate = $data['outcome_template'] ?? null;

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomePeriod academicPeriodId=' . json_encode($academicPeriodId) . ' outcomeTemplate=' . json_encode($outcomeTemplate)); //[TEMP-LOG]
            //POCOR-9584: end

            $outcomePeriodOptions = [];
            if (!is_null($request->getData('ImportOutcomeResults')['outcome_template'])) {
                $outcomePeriodOptions = $this->OutcomePeriods
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([
                        $this->OutcomePeriods->aliasField('academic_period_id IS') => $academicPeriodId,
                        $this->OutcomePeriods->aliasField('outcome_template_id IS') => $request->getData('ImportOutcomeResults')['outcome_template']
                    ])
                    ->toArray();
            }

            //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
            Log::debug('@ImportOutcomeResultsTable::onUpdateFieldOutcomePeriod outcomePeriodOptions count=' . count($outcomePeriodOptions)); //[TEMP-LOG]
            //POCOR-9584: end
            $attr['options'] = $outcomePeriodOptions;
            // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeOutcomePeriod';
        }
        return $attr;
    }

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::onImportModelSpecificValidation START tempRow=' . json_encode($tempRow->getArrayCopy())); //[TEMP-LOG]
        //POCOR-9584: end

        $requestData = $this->request->getData()[$this->getAlias()];
        $tempRow['academic_period_id'] = $requestData['academic_period'];
        $tempRow['outcome_template_id'] = $requestData['outcome_template'];
        $tempRow['outcome_period_id'] = $requestData['outcome_period'];
        $tempRow['institution_class_id'] = $requestData['class'];
        $tempRow['education_subject_id'] = $requestData['education_subject'];
        $tempRow['institution_id'] = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::onImportModelSpecificValidation requestData=' . json_encode($requestData)); //[TEMP-LOG]
        Log::debug('@ImportOutcomeResultsTable::onImportModelSpecificValidation institution_id=' . json_encode($tempRow['institution_id']) . ' outcome_criteria_id=' . json_encode($tempRow['outcome_criteria_id'] ?? 'not_set')); //[TEMP-LOG]
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
        Log::debug('@ImportOutcomeResultsTable::onImportModelSpecificValidation outcomeCriteriaEntity=' . json_encode($outcomeCriteriaEntity ? $outcomeCriteriaEntity->toArray() : null)); //[TEMP-LOG]
        //POCOR-9584: end

            $tempRow['education_subject_id'] = $outcomeCriteriaEntity->education_subject_id;
            $tempRow['education_grade_id'] = $outcomeCriteriaEntity->_matchingData['Templates']->education_grade_id;

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        Log::debug('@ImportOutcomeResultsTable::onImportModelSpecificValidation END education_subject_id=' . json_encode($tempRow['education_subject_id']) . ' education_grade_id=' . json_encode($tempRow['education_grade_id'])); //[TEMP-LOG]
        //POCOR-9584: end

        return true;
    }
}

