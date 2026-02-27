<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;

class EducationGradesTable extends ControllerActionTable
{
    protected $_accessible = [
        'order' => true,
    ];
    private $_contain = ['EducationSubjects._joinData'];
    private $_fieldOrder = ['name', 'code', 'education_stage_id', 'admission_age', 'education_programme_id', 'visible'];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsToMany('EducationInstitutions', [ //POCOR-8507 association names are unique
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
        $this->hasMany('EducationInstitutionStudents',       ['className' => 'Institution.Students', 'dependent' => true, 'cascadeCallbacks' => true]);
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
            // $this->behaviors()->get('Reorder')->config([
            //     'filter' => 'education_programme_id',
            // ]);
            $reorderBehavior = $this->behaviors()->get('Reorder');
            $reorderBehavior->setConfig('filter', 'education_programme_id');
        }
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index']
        ]);
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'education_grade_create',
                'entity_delete' => 'education_grade_delete',
                'entity_update' => 'education_grade_update',
                'table_alias' => 'Education.EducationGrades',
                'contain' => []
            ]
        ); // for webhook
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
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

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.afterReorder'] = 'afterReorder';

        return $events;
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options) {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        if (!$entity->isNew()) {
            if ($entity->setVisible) {
                // to be revisit
                // $EducationGradesSubjects = TableRegistry::getTableLocator()->get('EducationGradesSubjects');
                // $EducationGradesSubjects->updateAll(
                //  ['visible' => 0],
                //  ['education_grade_id' => $entity->id]
                // );
            }
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        // Ensure auto-quoting for DB safety (kept from your original)
        $this->getConnection()->getDriver()->enableAutoQuoting();

    }
    //POCOR 7308 starts
    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
            //deleting institution subjects entry end
        //$institutionStudents = $this->institutionstudents;
        //print_r($institutionStudents->exists([$institutionStudents->aliasField($institutionStudents->foreignKey()) => $entity->id]));
        //POCOR-7179[START] delete custom field becouse when user is created from directory it insert value in custom field
        // TableRegistry::getTableLocator()->get('student_custom_field_values')->deleteAll(['student_id' => $entity->id]);
        //POCOR-7179[END]
        if($this->checkUsersChildRecords($entity)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset'=>true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
         else{
            //deleting issue of isnstitution_subject
            $institutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects')
                ->find()->where(['education_grade_id' => $entity->id])->first();
            if($institutionSubjects){
                TableRegistry::getTableLocator()->get('institution_subjects')->delete($institutionSubjects);
            }

            $educationGradeTable = TableRegistry::getTableLocator()->get('Education.EducationGrades')
                ->find()->where(['id' => $entity->id])->first();
               if(TableRegistry::getTableLocator()->get('Education.EducationGrades')->delete($entity)){
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
            $institutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all assessments
            $assessments = TableRegistry::getTableLocator()->get('Assessment.Assessments')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_fees
            $institutionFees = TableRegistry::getTableLocator()->get('Institution.InstitutionFees')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_quality_rubrics
            $institutionQualityRubrics = TableRegistry::getTableLocator()->get('Institution.InstitutionRubrics')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_class_grades
            $institutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_class_students
            $institutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_students
            $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_student_admission
            $institutionStudentAdmission = TableRegistry::getTableLocator()->get('Institution.StudentAdmission')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all institution_student_withdraw
            $institutionStudentWithdraw = TableRegistry::getTableLocator()->get('Institution.StudentWithdraw')
                ->find()->where(['education_grade_id' => $educationGradeId])->count();

            // count all education_grades_subjects
            $educationGradesSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects')
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

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        // Preserve existing logic
        $this->updateAdmissionAgeAfterDelete($entity);

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
                    $nextProgrammesGradesOptions = TableRegistry::getTableLocator()->get('Education.EducationProgrammesNextProgrammes')->getNextProgrammeFirstGradeList($programmeId);
                } else {
                    $nextProgrammesGradesOptions = TableRegistry::getTableLocator()->get('Education.EducationProgrammesNextProgrammes')->getNextGradeList($programmeId);
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
                    $nextProgrammesGradesOptions = TableRegistry::getTableLocator()->get('Education.EducationProgrammesNextProgrammes')->getNextProgrammeFirstGradeList($programmeId);
                } else {
                    $nextProgrammesGradesOptions = TableRegistry::getTableLocator()->get('Education.EducationProgrammesNextProgrammes')->getNextGradeList($programmeId);
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
						// 'ToAcademicPeriods.order = AcademicPeriods.order-1',//POCOR-7689 for gd-moe-environment
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
                    $nextProgrammesGradesOptions = TableRegistry::getTableLocator()->get('Education.EducationProgrammesNextProgrammes')->getNextProgrammeFirstGradeList($programmeId);
                } else {
                    $nextProgrammesGradesOptions = TableRegistry::getTableLocator()->get('Education.EducationProgrammesNextProgrammes')->getNextGradeList($programmeId);
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

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        //$this->getAssociation('Education')->name('InstitutionProgrammes');
        $this->getAssociation('EducationInstitutions')->setName('EducationInstitutions');//POCOR-8507
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setFieldOrder($this->_fieldOrder);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $serverRequest = $this->request;
        /*list($levelOptions, $selectedLevel, $programmeOptions, $selectedProgramme) = array_values($this->_getSelectOptions());
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $this->controller->set(compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme'));
        $query->where([$this->aliasField('education_programme_id') => $selectedProgramme])
                ->order([ $this->aliasField('order') => 'ASC',
                          $this->aliasField('modified') => 'DESC'
                        ]); */
        // Academic period filter
        $EducationSystems = TableRegistry::getTableLocator()->get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($serverRequest->getQuery('academic_period_id')) ? $serverRequest->getQuery('academic_period_id') : $this->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        //level filter
        $levelOptions = $this->EducationProgrammes->EducationCycles->EducationLevels->getEducationLevelOptions($selectedAcademicPeriod);
        if (!empty($levelOptions)) {
            $selectedLevel = !empty($serverRequest->getQuery('level')) ? $serverRequest->getQuery('level') : key($levelOptions);
        } else{
            $levelOptions = ['0' => '-- '.__('No Education Level').' --'] + $levelOptions;
            $selectedLevel = !empty($serverRequest->getQuery('level')) ? $serverRequest->getQuery('level') : 0;
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
        $selectedProgramme = !is_null($serverRequest->getQuery('programme')) ? $serverRequest->getQuery('programme') : key($programmeOptions);
        $programmeOptions = $programmeOptions;
        if (!empty($programmeOptions )) {
            $selectedProgramme = !empty($serverRequest->getQuery('programme')) ? $serverRequest->getQuery('programme') : key($programmeOptions);
        } else {
            $programmeOptions = ['0' => '-- '.__('No Education Programme').' --'] + $programmeOptions;
            $selectedProgramme = !empty($serverRequest->getAttribute('query')['programme']) ?$serverRequest->getAttribute('query')['programme'] : 0;
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

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
        $this->field('subjects', ['type' => 'custom_subject', 'valueClass' => 'table-full-width']);
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['EducationSubjects']);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
    }

    public function setupFields(EventInterface $event, Entity $entity)
    {
        $this->field('name');
        $this->field('code');
        $this->field('admission_age', ['entity' => $entity]);
        $this->field('education_stage_id', ['entity' => $entity]);
        $this->field('education_programme_id', ['entity' => $entity]);
    }

    public function onGetCustomSubjectElement(EventInterface $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'index') {
            $EducationGradesSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects');
            $value = $EducationGradesSubjects
                ->findByEducationGradeId($entity->id)
                ->where([$EducationGradesSubjects->aliasField('visible') => 1])
                ->count();
            $attr['value'] = $value;
        } else if ($action == 'view') {
            $tableHeaders = [__('Name'), __('Code'), __('Hours Required')];
            $tableCells = [];

            $EducationGradesSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects');
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
                    $rowData[] = $event->getSubject()->Html->link(__($obj->name), [
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

        return $event->getSubject()->renderElement('Education.subjects', ['attr' => $attr]);
    }

    public function onUpdateFieldAdmissionAge(EventInterface $event, array $attr, $action, ServerRequest $request){
        list(, , $programmeOptions, $selectedProgramme) = array_values($this->_getSelectOptions());

        if ($action == 'add' && !empty($selectedProgramme)) {
            if (array_key_exists($this->getAlias(), $request->getData()) && array_key_exists('education_programme_id', $request->getData($this->getAlias()))) {
                $educationProgrammeId = $request->getData($this->getAlias())['education_programme_id'];
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

     public function onUpdateFieldEducationStageId(EventInterface $event, array $attr, $action, ServerRequest $request){
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

    public function onUpdateFieldEducationProgrammeId(EventInterface $event, array $attr, $action, ServerRequest $request)
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
        $serverRequest = $this->request;
        // Academic period filter
        $EducationSystems = TableRegistry::getTableLocator()->get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($serverRequest->getQuery('academic_period_id')) ? //POCOR-8897
                                    $serverRequest->getQuery('academic_period_id') :
                                    $this->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        //Return all required options and their key
        $levelOptions = $this->EducationProgrammes->EducationCycles->EducationLevels->getEducationLevelOptions($selectedAcademicPeriod);
        $selectedLevel = !is_null($serverRequest->getQuery('level')) ? $serverRequest->getQuery('level') : key($levelOptions);//POCOR-8897

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
        $selectedProgramme = !is_null($serverRequest->getQuery('programme')) ? $serverRequest->getQuery('programme') : key($programmeOptions);//POCOR-8897

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
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $UsersData = TableRegistry::getTableLocator()->get('User.Users');
        $studentStatuses = self::getDynamicTableInstance('student_statuses'); // POCOR-8231
        $institutionStudents = self::getDynamicTableInstance('institution_students'); // POCOR-8231
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
                if($studentsData->student_status_id == 6 || $studentsData->student_status_id == 7){
                    $studentStatusesValidateRepeater = $studentsData->education_grade_id;
                }
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
    /**
     * POCOR-8231
     * Gets a dynamic table instance with all associations.
     *
     * @param string $tableName The name of the table.
     * @return \Cake\ORM\Table The table instance.
     * @throws \Exception If the table instance cannot be retrieved.
     *
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
//            Log::debug('Error: ' . $e->getMessage());
        }

        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }

        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    // POCOR-9397 start
    public static function decodeMaybeB64Url(string $v): string {
        if (strncmp($v, 'b64.', 4) !== 0) return $v;
        $payload = substr($v, 4);
        // Convert URL-safe to standard Base64 and pad
        $payload = strtr($payload, '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode($payload, true);
        if ($decoded === false) return $v; // fall back if bad
        // Ensure UTF-8
        if (!mb_check_encoding($decoded, 'UTF-8')) return $v;
        return $decoded;
    }
    // POCOR-9397 end

    public function findRepeaterEducationGradeAddStudent(Query $query, array $options)
    {
        $validation = 'no';
        echo json_encode($validation);die;
        $educationGradeId = $options['education_grade_id'];
        $openemis_no = $options['openemis_no'];
        $first_name = self::decodeMaybeB64Url($options['first_name']); // POCOR-9397
        $last_name = self::decodeMaybeB64Url($options['last_name']); // POCOR-9397
        if(!$educationGradeId){
            $validation = 'no';
            echo json_encode($validation);die;
        }
        $educationGrade = $this->get($educationGradeId);
        if($educationGrade){
            $educationGradeName = $educationGrade->code;
        }else{
            $educationGradeName = "0";
        }

        $UsersData = TableRegistry::getTableLocator()->get('User.Users');

        $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents')
        ->extract('id')
        ->toArray();
        if(!empty($openemis_no)){
        	$result = $UsersData
            ->find()
            ->select(['id'])
            ->where(['openemis_no' => $openemis_no])
            ->first();
        }else{
        	$result = $UsersData
            ->find()
            ->select(['id'])
            ->where(['first_name' => $first_name, 'last_name'=>$last_name])
            ->first();
        }
        $studentId = $result->id;
        $studentStatusesValidateRepeater = '';
        if(isset($studentId)){
            $students =  $institutionStudents->find()->where(
                [
                    $institutionStudents->aliasField('student_id') => $studentId
                ])
                ->all();
            $validation = 'no';
            foreach($students AS $studentsData){
                $educationGradeName1 = $this->get($studentsData->education_grade_id)->code;
                if($educationGradeName == $educationGradeName1){
                    if($studentsData->student_status_id == 6 || $studentsData->student_status_id == 7){
                        $studentStatusesValidateRepeater = $studentsData->education_grade_id;
                    }
                }
            }
            $students =  $institutionStudents->find()->where(
                [
                    $institutionStudents->aliasField('education_grade_id') => $studentStatusesValidateRepeater,
                    $institutionStudents->aliasField('student_id') => $studentId, //POCOR-7386
                ])
                ->first();
        }
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

    public function afterReorder(EventInterface $event, $ids = [])
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
        $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
        $gradeOptions = $this->find('list', [
                'keyField' => 'id',
                'valueField' => 'programme_grade_name'
            ])
            ->LeftJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()],[
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
                    $nextProgrammesGradesOptions = TableRegistry::getTableLocator()->get('Education.EducationProgrammesNextProgrammes')->getNextProgrammeGradeList($nextProgrammeId, $periodId);
                } else {
                    $nextProgrammesGradesOptions = TableRegistry::getTableLocator()->get('Education.EducationProgrammesNextProgrammes')->getNextGradeList($programmeId);
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
    public function beforeAction(EventInterface $event, ArrayObject $extra)
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
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true){
        if ($field == 'name') {
            return __('Name');
        }elseif ($field == 'code') {
            return __('Code');
        }elseif ($field == 'education_level_id') {
            return __('Education Level');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'education_programme_id') {
            return __('Education Programme');
        }elseif ($field == 'visible') {
            return __('Visible');
        }elseif ($field == 'education_stage_id') {
            return __('Education Stage');
        }elseif ($field == 'subjects') {
            return __('Subject');
        }elseif ($field == 'admission_age') {
            return __('Admission Age');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}
