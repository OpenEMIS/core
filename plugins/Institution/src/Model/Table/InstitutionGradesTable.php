<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\I18n\Time;

use App\Model\Table\ControllerActionTable;

class InstitutionGradesTable extends ControllerActionTable
{
    private $institutionId;

    public function initialize(array $config)
    {
        $this->table('institution_grades');
        parent::initialize($config);

        $this->belongsTo('EducationGrades',             ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions',                ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index']
        ]);

        $this->toggle('search', false);
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('end_date')
            ->add('end_date', 'ruleCompareDateReverse', [
                    'rule' => ['compareDateReverse', 'start_date', true]
                ])
            ->add('end_date', 'ruleCheckStudentInEducationProgrammes', [
                    'rule' => ['checkStudentInEducationProgrammes']
                ])
            ->add('start_date', 'ruleCompareWithInstitutionDateOpened', [
                    'rule' => ['compareWithInstitutionDateOpened']
                ])
            ->requirePresence('programme')
            ;
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->institutionId = $this->Session->read('Institution.Institutions.id');
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->field('level');
        $this->field('programme');
        $this->field('end_date', ['default_date' => false]);
        $this->field('education_grade_id');

        if ($this->action == 'add') {
            $this->setFieldOrder([
                'level', 'programme', 'start_date', 'end_date', 'education_grade_id'
            ]);
        } else if ($this->action == 'index') {
            $this->setFieldOrder([
                'education_grade_id', 'programme', 'level', 'start_date', 'end_date'
            ]);
        } else if ($this->action == 'view' || $this->action == 'edit') {
            $this->setFieldOrder([
                'level', 'programme', 'education_grade_id', 'start_date', 'end_date'
            ]);
        }
    }


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels'])
            ->order([
                'EducationLevels.order' => 'ASC',
                'EducationCycles.order' => 'ASC',
                'EducationProgrammes.order' => 'ASC',
                'EducationGrades.order' => 'ASC',
            ]);
    }


/******************************************************************************************************************
**
** viewEdit action methods
**
******************************************************************************************************************/
    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels']);
    }


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $errors = $entity->errors();
        $process = function($model, $entity) use ($data, $errors) {
            /**
             * PHPOE-2117
             * Remove       $this->field('institution_programme_id', ['type' => 'hidden']);
             *
             * education_grade_id will always be empty
             * so if errors array is more than 1, other fields are having an error
             */
            if (empty($errors) || count($errors)==1) {
                if ($data->offsetExists('grades')) {
                    $gradeIsSelected = false;
                    $error = true;
                    $gradeEntities = [];
                    foreach ($data['grades'] as $key=>$grade) {
                        if ($grade['education_grade_id'] != 0) {
                            $error = false;
                            $gradeIsSelected = true;

                            // need to set programme value since it was marked as required in validationDefault()
                            $grade['programme'] = $entity->programme;
                            $grade['start_date'] = $entity->start_date;
                            $grade['institution_id'] = $entity->institution_id;
                            if ($entity->has('end_date')) {
                                $grade['end_date'] = $entity->end_date;
                            }

                            $gradeEntities[$key] = $this->newEntity($grade);
                            if ($gradeEntities[$key]->errors()) {
                                $error = true;
                            }
                        }
                    }
                    if ($error && $gradeIsSelected) {
                        $model->Alert->error($this->aliasField('failedSavingGrades'));
                        return false;
                    } else if (!$gradeIsSelected) {
                        $model->Alert->error($this->aliasField('noGradeSelected'));
                        return false;
                    } else {
                        foreach ($gradeEntities as $grade) {
                            $entity->education_grade_id = $grade->education_grade_id;
                            $this->save($grade);
                        }
                        return true;
                    }
                } else {
                    $model->Alert->error($this->aliasField('noGradeSelected'));
                    return false;
                }
            } else {
                $model->Alert->error($this->aliasField('noGradeSelected'));
                return false;
            }
        };

        if (empty($errors) || count($errors)==1) {
            $educationGradeCount = $this->EducationGrades->find('list')
                    ->find('visible')
                    ->find('order')
                    ->where([$this->EducationGrades->aliasField('education_programme_id') => $entity->programme])
                    ->count();
            $existingGradeCount = $this->find()
                    ->select([$this->EducationGrades->aliasField('name')])
                    ->contain([$this->EducationGrades->alias()])
                    ->where([
                        $this->EducationGrades->aliasField('education_programme_id') => $entity->programme,
                        $this->aliasField('institution_id') => $entity->institution_id
                    ])
                    ->count();

            if ($educationGradeCount == $existingGradeCount) {
                $this->Alert->warning($this->aliasField('allGradesAlreadyAdded'));
                $event->stopPropagation();
                return $this->controller->redirect($this->url('index'));
            } else {
                return $process;
            }
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $Institution = TableRegistry::get('Institution.Institutions');
        $institution = $Institution->find()->where([$Institution->aliasField($Institution->primaryKey()) => $this->institutionId])->first();

        if (empty($institution->date_opened)) {
            $institution->date_opened = new Time('01-01-1970');
            $Institution->save($institution);
        }

        $dateOpened = $institution->date_opened;
        try{
            $yearOpened = 1970;
            if (!empty($institution->year_opened)) {
                $yearOpened = $institution->year_opened;
            }
            $year = $dateOpened->format('Y');
            if ($yearOpened != $year) {
                $month = $dateOpened->format('m');
                $day = $dateOpened->format('d');
                $dateOpened = new Time($yearOpened.'-'.$month.'-'.$day);
                $institution->date_opened = $dateOpened;
                $Institution->save($institution);
            }
            $formatDate = $dateOpened->format('d-m-Y');
        } catch (\Exception $e) {
            $institution->date_opened = new Time('01-01-1970');
            $Institution->save($institution);
            $dateOpened = $institution->date_opened;
        }

        $this->fields['start_date']['value'] = isset($entity->start_date) ? $entity->start_date : $dateOpened;
        $this->fields['start_date']['date_options']['startDate'] = $dateOpened->format('d-m-Y');
        $this->fields['end_date']['date_options']['startDate'] = $dateOpened->format('d-m-Y');
    }

    public function addOnChangeLevel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $data[$this->alias()]['programme'] = 0;
    }


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $level = $entity->education_grade->education_programme->education_cycle->education_level->system_level_name;
        $programme = $entity->education_grade->education_programme;
        $this->fields['level']['attr']['value'] = $level;

        $this->fields['programme']['attr']['value'] = $programme->cycle_programme_name;
        $this->fields['programme']['value'] = $programme->id;
        $this->fields['education_grade_id']['attr']['value'] = $entity->education_grade->name;

        $Institution = TableRegistry::get('Institution.Institutions');
        $institution = $Institution->find()->where([$Institution->aliasField($Institution->primaryKey()) => $this->institutionId])->first();
        $this->fields['start_date']['date_options']['startDate'] = $institution->date_opened->format('d-m-Y');
        $this->fields['end_date']['date_options']['startDate'] = $institution->date_opened->format('d-m-Y');
    }


/******************************************************************************************************************
**
** specific field methods
**
******************************************************************************************************************/
    public function onGetLevel(Event $event, Entity $entity)
    {
        $level = $entity->education_grade->education_programme->education_cycle->education_level->system_level_name;
        return $level;
    }

    public function onGetProgramme(Event $event, Entity $entity)
    {
        return $programme = $entity->education_grade->education_programme->cycle_programme_name;;
    }

    public function onUpdateFieldLevel(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $EducationLevels = TableRegistry::get('Education.EducationLevels');
            $levelOptions = $EducationLevels->find('list', ['valueField' => 'system_level_name'])
            ->find('visible')
            ->find('order')
            ->toArray();
            $attr['empty'] = true;
            $attr['options'] = $levelOptions;
            $attr['onChangeReload'] = 'changeLevel';
        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldProgramme(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['empty'] = true;
            $attr['options'] = [];
            if ($this->request->is(['post', 'put'])) {
                $levelId = $this->request->data($this->aliasField('level'));
                $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
                $query = $EducationProgrammes->find('list', ['valueField' => 'cycle_programme_name'])
                ->find('visible')
                ->find('order')
                ->matching('EducationCycles', function($q) use ($levelId) {
                    return $q->find('visible')->where(['EducationCycles.education_level_id' => $levelId]);
                });

                $programmeOptions = $query->toArray();
                $attr['options'] = $programmeOptions;
                $attr['onChangeReload'] = 'changeProgramme';
            }
        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'element';
            $attr['element'] = 'Institution.Programmes/grades';
            if ($request->is(['post', 'put'])) {
                $programmeId = $request->data($this->aliasField('programme'));

                if (empty($programmeId)) {
                    $programmeId = 0;
                }
                $data = $this->EducationGrades->find()
                ->find('visible')
                ->find('order')
                ->where(['EducationGrades.education_programme_id' => $programmeId])
                ->all();

                $institutionId = $this->Session->read('Institution.Institutions.id');
                $exists = $this->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade_id'])
                ->where([$this->aliasField('institution_id') => $institutionId])
                ->toArray();

                $attr['data'] = $data;
                $attr['exists'] = $exists;
            }
        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
    public function getGradeOptionsForIndex($institutionsId, $academicPeriodId, $listOnly=true)
    {
        /**
         * PHPOE-2090, changed to find by AcademicPeriod function in PeriodBehavior.php
         */
        /**
         * PHPOE-2132, changed to find by AcademicPeriod function in PeriodBehavior.php with extra parameter to exclude finding grades within date range.
         * Common statements with getGradeOptions() were moved to _gradeOptions().
         */
        $query = $this->find('all')
                    ->find('AcademicPeriod', ['academic_period_id' => $academicPeriodId, 'beforeEndDate' => $this->aliasField('start_date')]);
        return $this->_gradeOptions($query, $institutionsId, $listOnly);
    }

    public function getGradeOptions($institutionsId, $academicPeriodId, $listOnly=true)
    {
        /**
         * PHPOE-2090, changed to find by AcademicPeriod function in PeriodBehavior.php
         */
        /**
         * PHPOE-2132, Common statements with getGradeOptionsForIndex() were moved to _gradeOptions().
         */

        // Get the current time.
        $currTime = Time::now();

        $query = $this->find('all')
                    ->find('AcademicPeriod', ['academic_period_id' => $academicPeriodId])
                    ->where([
                        'OR' => [
                            [$this->aliasField('end_date').' IS NULL'],
                            [$this->aliasField('end_date') . " >= '" . $currTime->format('Y-m-d') . "'"]
                        ]
                    ])
                    ;
        return $this->_gradeOptions($query, $institutionsId, $listOnly);
    }

    private function _gradeOptions(Query $query, $institutionsId, $listOnly)
    {
        $query->contain(['EducationGrades'])
            ->where(['InstitutionGrades.institution_id = ' . $institutionsId])
            ->order(['EducationGrades.education_programme_id', 'EducationGrades.order']);
        $data = $query->toArray();
        if($listOnly) {
            $list = [];
            foreach ($data as $key => $obj) {
                $list[$obj->education_grade->id] = $obj->education_grade->programme_grade_name;
            }
            return $list;
        } else {
            return $data;
        }
    }

    /**
     * Used by InstitutionClassesTable & InstitutionSubjectsTable.
     * This function resides here instead of inside AcademicPeriodsTable because the first query is to get 'start_date' and 'end_date'
     * of registered Programmes in the Institution.
     * @param  integer $model                    [description]
     * @param  array   $conditions               [description]
     * @return [type]                            [description]
     */
    public function getAcademicPeriodOptions($Alert, $conditions=[])
    {
        $query = $this->find('all')
                    ->select(['start_date', 'end_date'])
                    ->where($conditions)
                    ;
        $result = $query->toArray();
        $startDateObject = null;
        foreach ($result as $key=>$value) {
            $startDateObject = $this->getLowerDate($startDateObject, $value->start_date);
        }
        if (is_object($startDateObject)) {
            $startDate = $startDateObject->toDateString();
        } else {
            $startDate = $startDateObject;
        }

        $endDateObject = null;
        foreach ($result as $key=>$value) {
            $endDateObject = $this->getHigherDate($endDateObject, $value->end_date);
        }
        if (is_object($endDateObject)) {
            $endDate = $endDateObject->toDateString();
        } else {
            $endDate = $endDateObject;
        }

        $conditions = array_merge(array('end_date IS NULL'), $conditions);
        $query = $this->find('all')
                    ->where($conditions)
                    ;
        $nullDate = $query->count();

        $academicPeriodConditions = [];
        $academicPeriodConditions['parent_id >'] = 0;
        $academicPeriodConditions['end_date >='] = $startDate;
        if($nullDate == 0) {
            $academicPeriodConditions['start_date <='] = $endDate;
        } else {
            $academicPeriodConditions['end_date >='] = $startDate;
        }

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $query = $AcademicPeriods->find('list')
                    ->select(['id', 'name'])
                    ->where($academicPeriodConditions)
                    ->order('`order`')
                    ;
        $result = $query->toArray();
        if (empty($result)) {
            $Alert->warning('Institution.Institutions.noProgrammes');
            return [];
        } else {
            return $result;
        }
    }

    /**
     * Used by $this->getAcademicPeriodOptions()
     * @param  Time $a Time object
     * @param  Time $b Time object
     * @return Time    Time object
     */
    private function getLowerDate($a, $b)
    {
        if (is_null($a)) {
            return $b;
        }
        if (is_null($b)) {
            return $a;
        }
        return (($a->toUnixString() <= $b->toUnixString()) ? $a : $b);
    }

    /**
     * Used by $this->getAcademicPeriodOptions()
     * @param  Time $a Time object
     * @param  Time $b Time object
     * @return Time    Time object
     */
    private function getHigherDate($a, $b)
    {
        if (is_null($a)) {
            return $b;
        }
        if (is_null($b)) {
            return $a;
        }
        return (($a->toUnixString() >= $b->toUnixString()) ? $a : $b);
    }

    public function findEducationGradeInCurrentInstitution(Query $query, array $options)
    {
        $academicPeriodId = (array_key_exists('academic_period_id', $options))? $options['academic_period_id']: null;
        $institutionId = (array_key_exists('institution_id', $options))? $options['institution_id']: null;

        $query->contain('EducationGrades.EducationProgrammes');
        $query->where([
            $this->aliasField('institution_id') => $institutionId
        ]);
        if (!is_null($academicPeriodId)) {
            $query->find('academicPeriod', ['academic_period_id' => $academicPeriodId]);
        }
        $query->group([$this->aliasField('education_grade_id')]);

        return $query;
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $educationGradeId = $entity->education_grade_id;
        $entity->name = $EducationGrades->get($educationGradeId)->name;
        $institutionId = $entity->institution_id;

        $InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $associatedStudentRecordsCount = $InstitutionStudents->find()
            ->where([
                $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
                $InstitutionStudents->aliasField('institution_id') => $institutionId
            ])
            ->count();
        $extra['associatedRecords'][] = ['model' => 'InstitutionStudents', 'count' => $associatedStudentRecordsCount];

        // to get the institution_class_id related to the education_grade_id
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $associatedClassObj = $InstitutionClassGrades->find()
            ->where([
                $InstitutionClassGrades->aliasField('education_grade_id') => $educationGradeId,
            ])
            ->toArray();

        // will check if the institution_class_id are in the Institutions.
        $associatedClassCount = 0;
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        foreach ($associatedClassObj as $key => $obj) {
            $institutionsClassId = $obj['institution_class_id'];
            $count = $InstitutionClasses->find()
                ->where([
                    $InstitutionClasses->aliasField('id') => $institutionsClassId,
                    $InstitutionClasses->aliasField('institution_id') => $institutionId
                ])
                ->count();
            if ($count > 0) {
                $associatedClassCount++;
            }
        }

        $extra['associatedRecords'][] = ['model' => 'InstitutionClasses', 'count' => $associatedClassCount];
    }
}
