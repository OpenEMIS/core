<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class EducationGradesTable extends ControllerActionTable
{
    private $_contain = ['EducationSubjects._joinData'];
    private $_fieldOrder = ['name', 'code', 'education_stage_id', 'admission_age', 'education_programme_id', 'visible'];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsToMany('Institutions', [
            'className' => 'Institution.Institutions',
            'joinTable' => 'institution_grades',
            'foreignKey' => 'education_grade_id',
            'targetForeignKey' => 'Institution_id',
            'through' => 'Institution.InstitutionGrades',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsTo('EducationProgrammes',     ['className' => 'Education.EducationProgrammes']);
        $this->belongsTo('EducationStages',         ['className' => 'Education.EducationStages']);
        $this->hasMany('Assessments',               ['className' => 'Assessment.Assessments', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionFees',           ['className' => 'Institution.InstitutionFees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Rubrics',                   ['className' => 'Institution.InstitutionRubrics', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionClassGrades',    ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionClassStudents',  ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudents',       ['className' => 'Institution.Students', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAdmission',          ['className' => 'Institution.StudentAdmission', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentWithdraw',           ['className' => 'Institution.StudentWithdraw', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'education_grades_subjects',
            'foreignKey' => 'education_grade_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Education.EducationGradesSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
            // 'saveStrategy' => 'append'
        ]);

        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'education_programme_id',
            ]);
        }

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        if (isset($this->action) && $this->action == 'add') {
            $validator
                    ->add('code', 'ruleUnique', [
                        //'rule' => 'validateUnique',
                        'rule' => 'educationGradesCode',
                        'provider' => 'table'
                    ]);
            return $validator;
        } else {
            return $validator;
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.afterReorder'] = 'afterReorder';

        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
        if (!$entity->isNew()) {
            if ($entity->setVisible) {
                // to be revisit
                // $EducationGradesSubjects = TableRegistry::get('EducationGradesSubjects');
                // $EducationGradesSubjects->updateAll(
                //  ['visible' => 0],
                //  ['education_grade_id' => $entity->id]
                // );
            }
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options){
         // Webhook Education Grade create -- start
         if($entity->isNew()){
            $body = array();
            $body = [
                'education_programme_id' =>$entity->education_programme_id,
                'grade_name' =>$entity->name,
                'grade_id' =>$entity->id,
            ];
            $Webhooks = TableRegistry::get('Webhook.Webhooks');
            if ($this->Auth->user()) {
                $Webhooks->triggerShell('education_grade_create', ['username' => $username], $body);
            }
        }
        // Webhook Education Grade create -- end

        //webhook Education Grade update -- start
        if(!$entity->isNew()){
            $body = array();
            $body = [
                'education_programme_id' =>$entity->education_programme_id,
                'grade_name' =>$entity->name,
                'grade_id' =>$entity->id,
            ];
            $Webhooks = TableRegistry::get('Webhook.Webhooks');
            if ($this->Auth->user()) {
                $Webhooks->triggerShell('education_grade_update', ['username' => $username], $body);
            }
        }
        //webhook Education Grade update -- start
    }
    //POCOR 7308 starts
    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
            //deleting institution subjects entry end
        //$institutionStudents = $this->institutionstudents;
        //print_r($institutionStudents->exists([$institutionStudents->aliasField($institutionStudents->foreignKey()) => $entity->id]));
        //POCOR-7179[START] delete custom field becouse when user is created from directory it insert value in custom field
        // TableRegistry::get('student_custom_field_values')->deleteAll(['student_id' => $entity->id]);
        //POCOR-7179[END]
        if($this->checkUsersChildRecords($entity)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset'=>true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
         else{
            //deleting issue of isnstitution_subject
            $institutionSubjects = TableRegistry::get('institution_subjects')
                ->find()->where(['education_grade_id' => $entity->id])->first();
            if($institutionSubjects){
                TableRegistry::get('institution_subjects')->delete($institutionSubjects);
            }

            $educationGradeTable = TableRegistry::get('education_grades')
                ->find()->where(['id' => $entity->id])->first();
               if(TableRegistry::get('education_grades')->delete($entity)){
                $this->Alert->success('general.delete.success', ['reset'=>true]);
                return $this->controller->redirect(['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Grades']);
               }
         }

    }

    private function checkUsersChildRecords($entity)
    {
        $result = false;
        $educationGradeId = $entity->id ?? 0;

        if($educationGradeId) {
            // count all institution_grades
            $institutionGrades = TableRegistry::get('institution_grades')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all assessments
            $assessments = TableRegistry::get('assessments')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_fees
            $institutionFees = TableRegistry::get('institution_fees')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_quality_rubrics
            $institutionQualityRubrics = TableRegistry::get('institution_quality_rubrics')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_class_grades
            $institutionClassGrades = TableRegistry::get('institution_class_grades')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_class_students
            $institutionClassStudents = TableRegistry::get('institution_class_students')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_students
            $institutionStudents = TableRegistry::get('institution_students')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_student_admission
            $institutionStudentAdmission = TableRegistry::get('institution_student_admission')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_student_withdraw
            $institutionStudentWithdraw = TableRegistry::get('institution_student_withdraw')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all education_grades_subjects
            $educationGradesSubjects = TableRegistry::get('education_grades_subjects')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            if( $institutionGrades||
                $assessments ||
                $institutionFees ||
                $institutionQualityRubrics ||
                $institutionClassGrades ||
                $institutionClassStudents ||
                $institutionStudents ||
                $institutionStudentAdmission||
                $institutionStudentWithdraw ||
                 $educationGradesSubjects ) {
                $result = true;
            }
        }

        return $result;
    }
   //POCOR 7308 ends

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {

        $this->updateAdmissionAgeAfterDelete($entity);

        // Webhook Education Grade Delete -- Start

        $body = array();
        $body = [
            'grade_id' => $entity->id
        ];
        $Webhooks = TableRegistry::get('Webhook.Webhooks');
        if($this->Auth->user()){
            $Webhooks->triggerShell('education_grade_delete', ['username' => $username], $body);
        }
        // Webhook Education Grade Delete -- End
    }

     /**
     * Method to get the education system id for the particular grade given
     *
     * @param integer $gradeId The grade id to check for
     * @return integer Education system id that the grade belongs to
     */
    public function getEducationSystemId($gradeId) {
        $educationSystemId = $this->find()
            ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
            ->where([$this->aliasField('id') => $gradeId])
            ->first();
        return $educationSystemId->education_programme->education_cycle->education_level->education_system->id;
    }

     /**
     * Method to check the list of the grades that belongs to the education system
     *
     * @param integer $systemId The education system id to check for
     * @return array A list of the education system grades belonging to that particular education system
     */
    public function getEducationGradesBySystem($systemId) {
        $educationSystemId = $this->find('list', [
                'keyField' => 'id',
                'valueField' => 'id'
            ])
            ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
            ->where(['EducationSystems.id' => $systemId])->toArray();
        return $educationSystemId;
    }

    /**
    * Method to get the list of available grades by a given education grade
    *
    * @param integer $gradeId The grade to find the list of available education grades
    * @param bool|true $getNextProgrammeGrades If flag is set to false, it will only fetch all the education
    *                                           grades of the same programme. If set to true it will get all
    *                                           the grades of the next programmes plus the current programme grades
    * @param bool|true $firstGradeOnly If flag is set to true, it will fetch all first education
    *                                           grades of the next programme. If set to false it will get all
    *                                           the grades of the next programmes plus the current programme grades
    */
    public function getNextAvailableEducationGrades($gradeId, $getNextProgrammeGrades = false, $firstGradeOnly = false) {
        if (!empty($gradeId)) {
            $gradeObj = $this->get($gradeId);
            $programmeId = $gradeObj->education_programme_id;
            $order = $gradeObj->order;
            $gradeOptions = $this->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'programme_grade_name'
                ])
                ->find('visible')
                ->find('order')
                ->where([
                    $this->aliasField('education_programme_id') => $programmeId,
                    $this->aliasField('order').' > ' => $order
                ])
                ->order([$this->aliasField('order')])
                ->toArray();

            // Default is to get the list of grades with the next programme grades
            if ($getNextProgrammeGrades) {
                if ($firstGradeOnly) {
                    $nextProgrammesGradesOptions = TableRegistry::get('Education.EducationProgrammesNextProgrammes')->getNextProgrammeFirstGradeList($programmeId);
                } else {
                    $nextProgrammesGradesOptions = TableRegistry::get('Education.EducationProgrammesNextProgrammes')->getNextGradeList($programmeId);
                }
                $results = $gradeOptions + $nextProgrammesGradesOptions;
            } else {
                $results = $gradeOptions;
            }
            return $results;
        } else {
            return [];
        }
    }
    //POCOR-6362 starts
    public function getNextAvailableEducationGradesForTransfer($gradeId, $academicPeriodId, $getNextProgrammeGrades = true, $firstGradeOnly = false) {
        $getNextProgrammeGrades = true;
        $gradeObj = $this->get($gradeId);
        $programmeId = $gradeObj->education_programme_id;
        if (!empty($gradeId)) {
            $i = 0;
            $gradeOptionsData = $this
                ->find()
                ->select([
                    'id' => 'ToGrades.id',
                    'grade_name' => 'ToGrades.name',
                    'programme' => 'ToProgrammes.name',
                    'is_visible' => 'ToGrades.visible'
                ])
                ->innerJoin(
                    ['EducationProgrammes' => 'education_programmes'],
                    [
                        'EducationProgrammes.id = ' . $this->aliasField('education_programme_id'),
                        'EducationProgrammes.visible = ' . 1
                    ]
                )
                ->innerJoin(
                    ['EducationCycles' => 'education_cycles'],
                    [
                        'EducationCycles.id = EducationProgrammes.education_cycle_id',
                    ]
                )
                ->innerJoin(
                    ['EducationLevels' => 'education_levels'],
                    [
                        'EducationLevels.id = EducationCycles.education_level_id',
                    ]
                )
                ->innerJoin(
                    ['EducationSystems' => 'education_systems'],
                    [
                        'EducationSystems.id = EducationLevels.education_system_id',
                    ]
                )
                ->innerJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = EducationSystems.academic_period_id',
                    ]
                )
                ->leftJoin(
                    ['NextGrades' => 'education_grades'],
                    [
                        'NextGrades.order = ' . $this->aliasField('order+1'),
                        'NextGrades.education_programme_id = ' . $this->aliasField('education_programme_id'),
                    ]
                )
                ->innerJoin(
                    ['ToGrades' => 'education_grades'],
                    [
                        'ToGrades.code = NextGrades.code',
                    ]
                )
                ->innerJoin(
                    ['ToProgrammes' => 'education_programmes'],
                    [
                        'ToProgrammes.id = ToGrades.education_programme_id',
                    ]
                )
                ->innerJoin(
                    ['ToCycles' => 'education_cycles'],
                    [
                        'ToCycles.id = ToProgrammes.education_cycle_id',
                    ]
                )
                ->innerJoin(
                    ['ToLevels' => 'education_levels'],
                    [
                        'ToLevels.id = ToCycles.education_level_id',
                    ]
                )
                ->innerJoin(
                    ['ToSystems' => 'education_systems'],
                    [
                        'ToSystems.id = ToLevels.education_system_id',
                    ]
                )
                ->leftJoin(
                    ['ToAcademicPeriods' => 'academic_periods'],
                    [
                        'ToAcademicPeriods.id = ToSystems.academic_period_id',
                        'ToAcademicPeriods.order = AcademicPeriods.order-1',
                    ]
                )
                ->where([
                    //$this->aliasField('id') => $gradeId,
                    'NextGrades.visible' => 1,
                    'ToGrades.visible' => 1,//POCOR-6498
                    'ToAcademicPeriods.id' => $academicPeriodId
                ])
                ->order([$this->aliasField('id')])
                ->toArray();

            $gradeOptions = [];
            foreach($gradeOptionsData as $key => $data) {
                $gradeOptions[$data->id] = $data->programme . ' - ' .$data->grade_name;
            }

            // Default is to get the list of grades with the next programme grades
            if ($getNextProgrammeGrades) {

                if ($firstGradeOnly) {
                    $nextProgrammesGradesOptions = TableRegistry::get('Education.EducationProgrammesNextProgrammes')->getNextProgrammeFirstGradeList($programmeId);
                } else {
                    $nextProgrammesGradesOptions = TableRegistry::get('Education.EducationProgrammesNextProgrammes')->getNextGradeList($programmeId);
                }
                $results = $gradeOptions + $nextProgrammesGradesOptions;
            } else {
                $results = $gradeOptions;
            }
            $$i=0;
            foreach ($results as $key => $value) {
                if($i==0){
                 $result[$key]=$value;
                }
            $i++;}
            //print_r($result); exit;
            return $result;
        } else {
            return [];
        }
    }//POCOR-6362 ends

	public function getNextAvailableEducationGradesForPromoted($gradeId, $academicPeriodId, $getNextProgrammeGrades = true, $firstGradeOnly = false) {

		if (!empty($gradeId)) {
            $gradeOptionsData = $this
				->find()
				->select([
					'id' => 'ToGrades.id',
					'grade_name' => 'ToGrades.name',
					'programme' => 'ToProgrammes.name'
				])
				->innerJoin(
					['EducationProgrammes' => 'education_programmes'],
					[
						'EducationProgrammes.id = ' . $this->aliasField('education_programme_id'),
					]
				)
				->innerJoin(
					['EducationCycles' => 'education_cycles'],
					[
						'EducationCycles.id = EducationProgrammes.education_cycle_id',
					]
				)
				->innerJoin(
					['EducationLevels' => 'education_levels'],
					[
						'EducationLevels.id = EducationCycles.education_level_id',
					]
				)
				->innerJoin(
					['EducationSystems' => 'education_systems'],
					[
						'EducationSystems.id = EducationLevels.education_system_id',
					]
				)
				->innerJoin(
					['AcademicPeriods' => 'academic_periods'],
					[
						'AcademicPeriods.id = EducationSystems.academic_period_id',
					]
				)
				->leftJoin(
					['NextGrades' => 'education_grades'],
					[
						'NextGrades.order = ' . $this->aliasField('order+1'),
						'NextGrades.education_programme_id = ' . $this->aliasField('education_programme_id'),
					]
				)
				->innerJoin(
					['ToGrades' => 'education_grades'],
					[
						'ToGrades.code = NextGrades.code',
					]
				)
				->innerJoin(
					['ToProgrammes' => 'education_programmes'],
					[
						'ToProgrammes.id = ToGrades.education_programme_id',
					]
				)
				->innerJoin(
					['ToCycles' => 'education_cycles'],
					[
						'ToCycles.id = ToProgrammes.education_cycle_id',
					]
				)
				->innerJoin(
					['ToLevels' => 'education_levels'],
					[
						'ToLevels.id = ToCycles.education_level_id',
					]
				)
				->innerJoin(
					['ToSystems' => 'education_systems'],
					[
						'ToSystems.id = ToLevels.education_system_id',
					]
				)
				->leftJoin(
					['ToAcademicPeriods' => 'academic_periods'],
					[
						'ToAcademicPeriods.id = ToSystems.academic_period_id',
						'ToAcademicPeriods.order = AcademicPeriods.order-1',
					]
				)
                ->where([
                    $this->aliasField('id') => $gradeId,
                    'ToAcademicPeriods.id' => $academicPeriodId,
                ])
                ->order([$this->aliasField('id')])
                ->toArray();

			$gradeOptions = [];
			foreach($gradeOptionsData as $data) {
				$gradeOptions[$data->id] = $data->programme . ' - ' .$data->grade_name;
			}

            // Default is to get the list of grades with the next programme grades
            if ($getNextProgrammeGrades) {
                if ($firstGradeOnly) {
                    $nextProgrammesGradesOptions = TableRegistry::get('Education.EducationProgrammesNextProgrammes')->getNextProgrammeFirstGradeList($programmeId);
                } else {
                    $nextProgrammesGradesOptions = TableRegistry::get('Education.EducationProgrammesNextProgrammes')->getNextGradeList($programmeId);
                }
                $results = $gradeOptions + $nextProgrammesGradesOptions;
            } else {
                $results = $gradeOptions;
            }
            return $results;
        } else {
            return [];
        }
    }

	public function getNextAvailableEducationGradesForRepeated($gradeId, $academicPeriodId) {

		if (!empty($gradeId)) {
            $gradeOptions = $this
				->find()
				->select([
					'id' => 'ToGrades.id',
					'grade_name' => 'ToGrades.name',
					'programme' => 'ToProgrammes.name'
				])
				->innerJoin(
					['EducationProgrammes' => 'education_programmes'],
					[
						'EducationProgrammes.id = ' . $this->aliasField('education_programme_id'),
					]
				)
				->innerJoin(
					['EducationCycles' => 'education_cycles'],
					[
						'EducationCycles.id = EducationProgrammes.education_cycle_id',
					]
				)
				->innerJoin(
					['EducationLevels' => 'education_levels'],
					[
						'EducationLevels.id = EducationCycles.education_level_id',
					]
				)
				->innerJoin(
					['EducationSystems' => 'education_systems'],
					[
						'EducationSystems.id = EducationLevels.education_system_id',
					]
				)
				->innerJoin(
					['AcademicPeriods' => 'academic_periods'],
					[
						'AcademicPeriods.id = EducationSystems.academic_period_id',
					]
				)
				->leftJoin(
					['NextGrades' => 'education_grades'],
					[
						'NextGrades.order = ' . $this->aliasField('order'),
						'NextGrades.education_programme_id = ' . $this->aliasField('education_programme_id'),
					]
				)
				->innerJoin(
					['ToGrades' => 'education_grades'],
					[
						'ToGrades.code = NextGrades.code',
					]
				)
				->innerJoin(
					['ToProgrammes' => 'education_programmes'],
					[
						'ToProgrammes.id = ToGrades.education_programme_id',
					]
				)
				->innerJoin(
					['ToCycles' => 'education_cycles'],
					[
						'ToCycles.id = ToProgrammes.education_cycle_id',
					]
				)
				->innerJoin(
					['ToLevels' => 'education_levels'],
					[
						'ToLevels.id = ToCycles.education_level_id',
					]
				)
				->innerJoin(
					['ToSystems' => 'education_systems'],
					[
						'ToSystems.id = ToLevels.education_system_id',
					]
				)
				->leftJoin(
					['ToAcademicPeriods' => 'academic_periods'],
					[
						'ToAcademicPeriods.id = ToSystems.academic_period_id',
						'ToAcademicPeriods.order = AcademicPeriods.order-1',
					]
				)
                ->where([
                    $this->aliasField('id') => $gradeId,
                    'ToAcademicPeriods.id' => $academicPeriodId,
                ])
                ->order([$this->aliasField('id')])
                ->first();

            return $gradeOptions;
        } else {
            return [];
        }
    }

    public function isLastGradeInEducationProgrammes($gradeId)
    {
        if (!empty($gradeId)) {
            $nextAvailableEducationGrades = $this->getNextAvailableEducationGrades($gradeId, false);

            if (!count($nextAvailableEducationGrades)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $this->association('Institutions')->name('InstitutionProgrammes');
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder($this->_fieldOrder);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        //Add controls filter to index page
        $toolbarElements = [
            ['name' => 'Education.controls', 'data' => [], 'options' => []]
        ];

        $this->controller->set('toolbarElements', $toolbarElements);

        $this->field('admission_age', ['visible' => false]);
        $this->field('subjects', ['type' => 'custom_subject', 'valueClass' => 'table-full-width']);
        $this->field('education_stage_id');
        $this->fields['education_programme_id']['sort'] = ['field' => 'EducationProgrammes.name'];
        $this->fields['education_stage_id']['sort'] = ['field' => 'EducationStages.name'];

        $this->_fieldOrder = ['visible', 'name', 'admission_age', 'code', 'education_programme_id', 'education_stage_id', 'subjects'];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        /*list($levelOptions, $selectedLevel, $programmeOptions, $selectedProgramme) = array_values($this->_getSelectOptions());
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $this->controller->set(compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme'));
        $query->where([$this->aliasField('education_programme_id') => $selectedProgramme])
                ->order([ $this->aliasField('order') => 'ASC',
                          $this->aliasField('modified') => 'DESC'
                        ]); */
        // Academic period filter
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        //level filter
        $levelOptions = $this->EducationProgrammes->EducationCycles->EducationLevels->getEducationLevelOptions($selectedAcademicPeriod);
        if (!empty($levelOptions)) {
            $selectedLevel = !empty($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);
        } else{
            $levelOptions = ['0' => '-- '.__('No Education Level').' --'] + $levelOptions;
            $selectedLevel = !empty($this->request->query('level')) ? $this->request->query('level') : 0;
        }

        $this->controller->set(compact('levelOptions', 'selectedLevel'));

        $cycleIds = $this->EducationProgrammes->EducationCycles
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->find('visible')
            ->where([$this->EducationProgrammes->EducationCycles->aliasField('education_level_id') => $selectedLevel])
            ->toArray();

        if (is_array($cycleIds) && !empty($cycleIds)) {
            $cycleIds = implode(', ', $cycleIds);
        } else {
            $cycleIds = 0;
        }

        $EducationProgrammes = $this->EducationProgrammes;
        $programmeOptions = $EducationProgrammes
            ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
            ->find('visible')
            ->contain(['EducationCycles'])
            ->order([
                $EducationProgrammes->EducationCycles->aliasField('order'),
                $EducationProgrammes->aliasField('order')
            ])
            ->where([
                $EducationProgrammes->aliasField('education_cycle_id') . ' IN (' .  $cycleIds . ')'
            ])
            ->toArray();
        $selectedProgramme = !is_null($this->request->query('programme')) ? $this->request->query('programme') : key($programmeOptions);
        $programmeOptions = $programmeOptions;
        if (!empty($programmeOptions )) {
            $selectedProgramme = !empty($this->request->query('programme')) ? $this->request->query('programme') : key($programmeOptions);
        } else {
            $programmeOptions = ['0' => '-- '.__('No Education Programme').' --'] + $programmeOptions;
            $selectedProgramme = !empty($this->request->query('programme')) ? $this->request->query('programme') : 0;
        }

        $this->controller->set(compact('programmeOptions', 'selectedProgramme'));
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where([$this->aliasField('education_programme_id') => $selectedProgramme])
                        ->order([$this->aliasField('order') => 'ASC']);

        $sortList = ['order', 'name', 'code', 'EducationProgrammes.name', 'EducationStages.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
        $this->field('subjects', ['type' => 'custom_subject', 'valueClass' => 'table-full-width']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['EducationSubjects']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
    }

    public function setupFields(Event $event, Entity $entity)
    {
        $this->field('name');
        $this->field('code');
        $this->field('admission_age', ['entity' => $entity]);
        $this->field('education_stage_id', ['entity' => $entity]);
        $this->field('education_programme_id', ['entity' => $entity]);
    }

    public function onGetCustomSubjectElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'index') {
            $EducationGradesSubjects = TableRegistry::get('EducationGradesSubjects');
            $value = $EducationGradesSubjects
                ->findByEducationGradeId($entity->id)
                ->where([$EducationGradesSubjects->aliasField('visible') => 1])
                ->count();
            $attr['value'] = $value;
        } else if ($action == 'view') {
            $tableHeaders = [__('Name'), __('Code'), __('Hours Required')];
            $tableCells = [];

            $EducationGradesSubjects = TableRegistry::get('EducationGradesSubjects');
            $gradeSubjectData = $EducationGradesSubjects
                ->findByEducationGradeId($entity->id)
                ->find('list', ['keyField' =>  'education_subject_id', 'valueField' => 'id'])
                ->where([$EducationGradesSubjects->aliasField('visible') => 1])
                ->toArray();

            $educationSubjects = $entity->extractOriginal(['education_subjects']);
            foreach ($educationSubjects['education_subjects'] as $key => $obj) {
                if ($obj->_joinData->visible == 1) {
                    $gradeSubjectId = $obj->id;

                    $rowData = [];
                    // link subject to GradeSubjects
                    $rowData[] = $event->subject()->Html->link(__($obj->name), [
                        'plugin' => 'Education',
                        'controller' => 'Educations',
                        'action' => 'GradeSubjects',
                        '0' => 'view',
                        '1' => $this->paramsEncode(['education_grade_id' => $entity->id, 'education_subject_id' => $gradeSubjectId])
                    ]);
                    $rowData[] = $obj->code;
                    $rowData[] = $obj->_joinData->hours_required;
                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $event->subject()->renderElement('Education.subjects', ['attr' => $attr]);
    }

    public function onUpdateFieldAdmissionAge(Event $event, array $attr, $action, Request $request)
    {
        list(, , $programmeOptions, $selectedProgramme) = array_values($this->_getSelectOptions());

        if ($action == 'add' && !empty($selectedProgramme)) {
            if (array_key_exists($this->alias(), $request->data) && array_key_exists('education_programme_id', $request->data[$this->alias()])) {
                $educationProgrammeId = $request['data'][$this->alias()]['education_programme_id'];
            } else {
                $educationProgrammeId = $selectedProgramme;
            }

            $value = 0;
            if ($educationProgrammeId > 0) {
                $educationCycleId = $this->EducationProgrammes->get($educationProgrammeId)->education_cycle_id;
                $admissionAge = $this->EducationProgrammes->EducationCycles->get($educationCycleId)->admission_age;

                $count = $this->find()
                    ->where([$this->aliasField('education_programme_id') => $educationProgrammeId])
                    ->count()
                ;

                $value = $admissionAge + $count;
            }

            $attr['value'] = $value; // saved value
            $attr['attr']['value'] = $value; // display
        } else if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['value'] = $entity->admission_age; // saved value
        }

        $attr['type'] = 'readonly';
        return $attr;
    }

    public function onUpdateFieldEducationStageId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $stageOptions = $this->EducationStages
                ->find('list')
                ->find('visible')
                ->find('order')
                ->all();

            $attr['type'] = 'select';
            $attr['options'] = $stageOptions;
        }

        return $attr;
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        list(, , $programmeOptions, $selectedProgramme) = array_values($this->_getSelectOptions());
        $attr['onChangeReload'] = true;
        $attr['options'] = $programmeOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedProgramme;
        } else if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->education_programme_id; // saved value
        }

        return $attr;
    }

    public function _getSelectOptions()
    {
        // Academic period filter
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        //Return all required options and their key
        $levelOptions = $this->EducationProgrammes->EducationCycles->EducationLevels->getEducationLevelOptions($selectedAcademicPeriod);
        $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

        $cycleIds = $this->EducationProgrammes->EducationCycles
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->find('visible')
            ->where([$this->EducationProgrammes->EducationCycles->aliasField('education_level_id') => $selectedLevel])
            ->toArray();

        if (is_array($cycleIds) && !empty($cycleIds)) {
            $cycleIds = implode(', ', $cycleIds);
        } else {
            $cycleIds = 0;
        }

        $EducationProgrammes = $this->EducationProgrammes;
        $programmeOptions = $EducationProgrammes
            ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
            ->find('visible')
            ->contain(['EducationCycles'])
            ->order([
                $EducationProgrammes->EducationCycles->aliasField('order'),
                $EducationProgrammes->aliasField('order')
            ])
            ->where([
                $EducationProgrammes->aliasField('education_cycle_id') . ' IN (' .  $cycleIds . ')'
            ])
            ->toArray();
        $selectedProgramme = !is_null($this->request->query('programme')) ? $this->request->query('programme') : key($programmeOptions);

        return compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme');
    }

    public function getEducationGradesByProgrammes($programmeId)
    {
        $gradeOptions = $this
                        ->find('list')
                        ->find('visible')
                        ->contain(['EducationProgrammes'])
                        ->where([$this->aliasField('education_programme_id') => $programmeId])
                        ->order(['EducationProgrammes.order' => 'ASC', $this->aliasField('order') => 'ASC'])
                        ->toArray();

        return $gradeOptions;
    }

    public function getEducationGrades()
    {
        $gradeOptions = $this
                        ->find('list')
                        ->find('visible')
                        ->order([$this->aliasField('name') => 'ASC'])
                        ->toArray();

        return $gradeOptions;
    }

    public function findGradeSubjectsByProgramme(Query $query, $options)
    {
        $educationProgrammeId = $options['education_programme_id'];
        $query
            ->find('visible')
            ->contain(['EducationSubjects'])
            ->where([$this->aliasField('education_programme_id') => $educationProgrammeId])
            ->order([$this->aliasField('order')]);

        return $query;
    }

    public function findRepeaterEducationGrade(Query $query, array $options)
    {
        $educationGradeId = $options['education_grade_id'];
        $openemis_no = $options['openemis_no'];
        $educationGradeName = $this->get($educationGradeId)->code;
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $UsersData = TableRegistry::get('User.Users');
        $studentStatuses = TableRegistry::get('student_statuses');
        $institutionStudents = TableRegistry::get('institution_students');
        $EducationGradesData = $EducationGrades->find()
        ->where([
            $EducationGrades->aliasField('code') => $educationGradeName
        ])
        ->extract('id')
        ->toArray();
        $result = $UsersData
            ->find()
            ->select(['id'])
            ->where(['openemis_no' => $openemis_no])
            ->first();
        $studentId = $result->id;
        $studentStatusesValidateRepeater = '';
        $students =  $institutionStudents->find()->where(
            [
                $institutionStudents->aliasField('student_id') => $studentId
            ])
            ->all();
        $validation = 'no';
        foreach($students AS $studentsData){
            $educationGradeName1 = $this->get($studentsData->education_grade_id)->code;
            if($educationGradeName == $educationGradeName1){
                $studentStatusesValidateRepeater = $studentsData->education_grade_id;
            }
        }
        $students =  $institutionStudents->find()->where(
            [
                $institutionStudents->aliasField('education_grade_id') => $studentStatusesValidateRepeater,
            ])
            ->first();
        if(empty($students)){
            $validation = 'no';
        }else{
            $validation = 'yes';
        }
        echo json_encode($validation);die;
    }


    public function getAdmissionAge($educationGradeId)
    {
        $entity = $this->get($educationGradeId, ['contain' => ['EducationProgrammes.EducationCycles']]);
        $admissionAge = $entity->education_programme->education_cycle->admission_age;

        $grades = $this->find('list')
            ->innerJoin(
                ['EducationGradesB' => 'education_grades'],
                [
                    'EducationGradesB.education_programme_id = ' . $this->aliasField('education_programme_id'),
                    'EducationGradesB.id' => $educationGradeId,
                ]
            )
            ->order([$this->aliasField('order')])
            ->toArray();

        foreach ($grades as $id => $value) {
            if ($id != $educationGradeId) {
                $admissionAge++;
            } else {
                break;
            }
        }
        return $admissionAge;
    }

    public function afterReorder(Event $event, $ids = [])
    {
        $gradeIds = array_column($ids, 'id');

        $this->updateAdmissionAge($gradeIds);
    }

    private function updateAdmissionAgeAfterDelete(Entity $entity)
    {
        $educationProgrammeId = $entity->education_programme_id;
        $gradeIds = $this->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->where([$this->aliasField('education_programme_id') => $educationProgrammeId])
            ->order([$this->aliasField('order')])
            ->toArray();

        $this->updateAdmissionAge($gradeIds);
    }

    private function updateAdmissionAge($gradeIds = [])
    {
        $admissionAge = null;
        $count = 0;
        foreach ($gradeIds as $id) {
            if (is_null($admissionAge)) {
                $entity = $this->get($id, ['contain' => ['EducationProgrammes.EducationCycles']]);
                $admissionAge = $entity->education_programme->education_cycle->admission_age;
            }

            $gradeAdmissionAge = $admissionAge + $count++;

            $this->updateAll(
                ['admission_age' => $gradeAdmissionAge],
                ['id' => $id]
            );
        }
    }

    /*POCOR-6257 Starts*/
    public function getEducationGradesByPeriod($academicPeriodId, $institutionId)
    {
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $gradeOptions = $this->find('list', [
                'keyField' => 'id',
                'valueField' => 'programme_grade_name'
            ])
            ->LeftJoin([$InstitutionGrades->alias() => $InstitutionGrades->table()],[
                    $this->aliasField('id').' = ' . $InstitutionGrades->aliasField('education_grade_id')
            ])
            ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
            ->where([
                'EducationSystems.academic_period_id' => $academicPeriodId,
                $InstitutionGrades->aliasField('institution_id') => $institutionId
            ])->toArray();

        return $gradeOptions;
    }
    /*POCOR-6257 ends*/

    /*POCOR-6498 starts*/
    public function getNextEducationGrades($gradeId, $periodId, $getNextProgrammeGrades = false, $firstGradeOnly = false, $nexteducationgradeforenrolledStatus = false) {
        if (!empty($gradeId)) {
            $gradeObj = $this->get($gradeId);
            $programmeId = $gradeObj->education_programme_id;
            $programmeObj = $this->EducationProgrammes->get($programmeId);
            $programmeCode = $programmeObj->code;
            $programmeName = $programmeObj->name;
            $nextProgrammeId = $this->EducationProgrammes->find()
                                ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                                ->where([
                                    'EducationSystems.academic_period_id' => $periodId,
                                    $this->EducationProgrammes->aliasField('code') => $programmeCode,
                                    'OR' => [
                                        $this->EducationProgrammes->aliasField('name') => $programmeName
                                    ]
                                ])
                                ->first()->id;
            $order = $gradeObj->order;
            //POCOR-6982 Starts
            if($nexteducationgradeforenrolledStatus == true){
                $gradeOptions = $this->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'programme_grade_name'
                    ])
                    ->find('visible')
                    ->find('order')
                    ->where([
                        $this->aliasField('education_programme_id') => $programmeId,
                        $this->aliasField('order') => $order
                    ])
                    ->order([$this->aliasField('order')])
                    ->limit(1)
                    ->toArray();//POCOR-6982 Ends
            }else{
                $gradeOptions = $this->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'programme_grade_name'
                ])
                ->find('visible')
                ->find('order')
                ->where([
                    $this->aliasField('education_programme_id') => $programmeId,
                    $this->aliasField('order').' > ' => $order
                ])
                ->order([$this->aliasField('order')])
                ->toArray();
            }
            // Default is to get the list of grades with the next programme grades
            if ($getNextProgrammeGrades) {
                if ($firstGradeOnly) {
                    $nextProgrammesGradesOptions = TableRegistry::get('Education.EducationProgrammesNextProgrammes')->getNextProgrammeGradeList($nextProgrammeId, $periodId);
                } else {
                    $nextProgrammesGradesOptions = TableRegistry::get('Education.EducationProgrammesNextProgrammes')->getNextGradeList($programmeId);
                }
                //POCOR-6982 Starts
                if($nexteducationgradeforenrolledStatus == true){
                    $results = $gradeOptions;
                }else{
                    $results = $gradeOptions + $nextProgrammesGradesOptions;
                }//POCOR-6982 Ends
            } else {
                $results = $gradeOptions;
            }
            return $results;
        } else {
            return [];
        }
    }
    /*POCOR-6498 ends*/

    /*POCOR-6498 Starts*/
    public function getNextEducationGradesForTransfer($gradeId, $academicPeriodId, $getNextProgrammeGrades = true, $firstGradeOnly = false, $nexteducationgradeforenrolledStatus = false) {//add $nexteducationgradeforenrolledStatus POCOR-6230
        $gradeObj = $this->get($gradeId);
        $programmeId = $gradeObj->education_programme_id;
        $programmeObj = $this->EducationProgrammes->get($programmeId);
        $programmeCode = $programmeObj->code;
        $programmeName = $programmeObj->name;
        $nextProgrammeId = $this->EducationProgrammes->find()
                                ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                                ->where([
                                    'EducationSystems.academic_period_id' => $academicPeriodId,
                                    $this->EducationProgrammes->aliasField('code') => $programmeCode,
                                    'OR' => [
                                        $this->EducationProgrammes->aliasField('name') => $programmeName
                                    ]
                                ])
                                ->first()->id;
        $order = $gradeObj->order;
        //POCOR-6230 Starts
        if($nexteducationgradeforenrolledStatus == true){
            $gradeOptions = $this->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'programme_grade_name'
                ])
                ->find('visible')
                ->find('order')
                ->where([
                    $this->aliasField('education_programme_id') => $nextProgrammeId,
                    $this->aliasField('order') => $order
                ])
                ->order([$this->aliasField('order')])
                ->limit(1)
                ->toArray();//POCOR-6230 Ends
        }else{
            $gradeOptions = $this->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'programme_grade_name'
                ])
                ->find('visible')
                ->find('order')
                ->where([
                    $this->aliasField('education_programme_id') => $nextProgrammeId,
                    $this->aliasField('order').' > ' => $order
                ])
                ->order([$this->aliasField('order')])
                ->limit(1)
                ->toArray();
        }
        return $gradeOptions;
    }
    /*POCOR-6498 ends*/

    // Start POCOR-5188
    public function beforeAction(Event $event, ArrayObject $extra)
    {
		$is_manual_exist = $this->getManualUrl('Administration','Education Grades','Education');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
    }
    // End POCOR-5188
}
