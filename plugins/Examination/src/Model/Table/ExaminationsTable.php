<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;

class ExaminationsTable extends ControllerActionTable {
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ExaminationSubjects', ['className' => 'Examination.ExaminationSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationStudentSubjectResults', ['className' => 'Examination.ExaminationStudentSubjectResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('ExaminationCentres', [
            'className' => 'Examination.ExaminationCentres',
            'joinTable' => 'examination_centres_examinations',
            'foreignKey' => 'examination_id',
            'targetForeignKey' => 'examination_centre_id',
            'through' => 'Examination.ExaminationCentresExaminations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('ExaminationCentreRooms', [
            'className' => 'Examination.ExaminationCentreRooms',
            'joinTable' => 'examination_centre_rooms_examinations',
            'foreignKey' => 'examination_id',
            'targetForeignKey' => 'examination_centre_room_id',
            'through' => 'Examination.ExaminationCentreRoomsExaminations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
            ])
            // ->add('registration_start_date', [
            //     'ruleInAcademicPeriod' => [
            //         'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            //     ],
            //     'ruleCompareDate' => [
            //         'rule' => ['compareDate', 'registration_end_date', false]
            //     ]
            // ])
            // ->add('registration_end_date', 'ruleInAcademicPeriod', [
            //     'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            // ])
            ->requirePresence('examination_subjects');
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['ExaminationSubjects.EducationSubjects', 'ExaminationSubjects.ExaminationGradingTypes']);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra) {
        $this->field('description', ['visible' => false]);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Exams','Examinations');
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
		// End POCOR-5188
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
//        dd($selectedAcademicPeriod);
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        // POCOR-8919 start
        $serverRequest = $this->request;
        $selectedAcademicPeriod = !is_null($serverRequest->getQuery('academic_period_id')) ? $serverRequest->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        // POCOR-8919 end
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where($where);
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->controller->getExamsTab();
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // send to ctp
        $this->controller->set('examinationGradingTypeOptions', $this->getGradingTypeOptions());

        $this->setupFields($entity);
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // only can choose subject in add
        $subjects = [];
        if (!empty($this->request->getData()[$this->getAlias()]['education_grade_id'])) {
            $selectedGrade = $this->request->getData()[$this->getAlias()]['education_grade_id'];
            $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
            $subjects = $EducationSubjects->getEducationSubjectsByGrades($selectedGrade);
        }

        // send to ctp
        $this->controller->set('educationSubjectOptions', $subjects);
    }

    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data[$this->getAlias()]['examination_subjects']) || empty($data[$this->getAlias()]['examination_subjects'])) {
            $this->Alert->warning($this->aliasField('noExaminationSubjects'));
        }
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        // used to do validation for examination item date
        if (isset($data['examination_subjects'])) {
            $registrationEndDate = $data['registration_end_date'];
            foreach ($data['examination_subjects'] as $key => $value) {
                $data['examination_subjects'][$key]['registration_end_date'] = $registrationEndDate;
            }
        }
    }

    public function setupFields(Entity $entity)
    {
        $this->field('code');
        $this->field('name');
        $this->field('description');
        $this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('education_programme_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'select', 'entity' => $entity, 'empty' => true]);
        $this->field('registration_start_date');
        $this->field('registration_end_date');
        $this->field('examination_subjects', [
            'type' => 'element',
            'element' => 'Examination.examination_subjects'
        ]);
    }

    public function editBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['ExaminationSubjects.StudentResults']);
    }

    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data[$this->getAlias()]['examination_subjects']) || empty($data[$this->getAlias()]['examination_subjects'])) {
            $this->Alert->warning($this->aliasField('noExaminationSubjects'));
        }
    }

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $errors = $entity->getErrors();
        if (empty($errors)) {
            // manually delete hasMany Examination items
            $fieldKey = 'examination_subjects';
            if (!array_key_exists($fieldKey, $data[$this->getAlias()])) {
                $data[$this->getAlias()][$fieldKey] = [];
            }

            $savedExamItems = array_column($data[$this->getAlias()][$fieldKey], 'id');
            $originalExamItems = $entity->extractOriginal([$fieldKey])[$fieldKey];
            foreach ($originalExamItems as $key => $item) {
                if (!in_array($item['id'], $savedExamItems)) {
                    // check that there are no results for this examination item
                    if (!$item->has('student_results') || empty($item->student_results)) {
                        $this->ExaminationSubjects->delete($item);
                        unset($entity->examination_subjects[$key]);
                    }
                }
            }
        }
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->getQuery['period']));
				$attr['options'] = $periodOptions;
				$attr['onChangeReload'] = true;
                $attr['default'] = $selectedPeriod;

            } else {

                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->academic_period_id;
                $attr['attr']['value'] = $this->AcademicPeriods->get($attr['entity']->academic_period_id)->name;
            }
        }
        return $attr;
    }

    public function onUpdateFieldEducationProgrammeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view') {
            $attr['visible'] = false;

        } else if ($action == 'add' || $action == 'edit') {

            $EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');
			$AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
			$academicPeriodId = !is_null($request->getData($this->aliasField('academic_period_id'))) ? $request->getData($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();

            if ($action == 'add') {
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('visible')
					->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
                    ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
					->toArray();

                $attr['options'] = $programmeOptions;
                $attr['onChangeReload'] = 'changeEducationProgrammeId';

            } else {
                //since programme_id is not stored, then during edit need to get from grade
                $programmeId = $this->EducationGrades->get($attr['entity']->education_grade_id)->education_programme_id;
                $attr['type'] = 'readonly';
                $attr['value'] = $programmeId;
                $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
            }
        }
        return $attr;
    }

    public function addEditOnChangeEducationProgrammeId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->getQuery['programme']);
        unset($data[$this->getAlias()]['examination_subjects']);
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('education_programme_id', $request->getData()[$this->getAlias()])) {
                    $request->getQuery['programme'] = $request->getData()[$this->getAlias()]['education_programme_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                $selectedProgramme =  $request->getData()[$this->getAlias()]['education_programme_id'];
                $gradeOptions = [];

                if (!is_null($selectedProgramme)) {
                    $gradeOptions = $this->EducationGrades
                        ->find('list')
                        ->find('visible')
                        ->contain(['EducationProgrammes'])
                        ->where([$this->EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
                        ->order(['EducationProgrammes.order' => 'ASC', $this->EducationGrades->aliasField('order') => 'ASC'])
                        ->toArray();
                }
                $attr['options'] = $gradeOptions;
                $attr['onChangeReload'] = 'changeEducationGrade';

            } else {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->EducationGrades->get($attr['entity']->education_grade_id)->name;
            }
        }

        return $attr;
    }

    public function addOnChangeEducationGrade(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->getQuery['grade']);
        unset($data[$this->getAlias()]['examination_subjects']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('education_grade_id', $request->getData()[$this->getAlias()])) {
                    $selectedGrade = $request->getData()[$this->getAlias()]['education_grade_id'];
                    $request->getQuery['grade'] = $selectedGrade;
                }
            }
        }
    }

    public function addOnAddExaminationItem(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $fieldKey = 'examination_subjects';

        if (empty($data[$this->getAlias()][$fieldKey])) {
            $data[$this->getAlias()][$fieldKey] = [];
        }

        if ($data->offsetExists($this->getAlias())) {
            $data[$this->getAlias()][$fieldKey][] = [
                'code' => '',
                'name' => '',
                'weight' => '',
                'examination_grading_type_id' => ''
            ];
        }

        $options['associated'] = [
            'ExaminationSubjects' => ['validate' => false]
        ];
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public function getExaminationOptions($selectedAcademicPeriod = null)
    {
        // POCOR-8919 start
        $where = [];
        if($selectedAcademicPeriod) {
            $where = [$this->aliasField('academic_period_id') => $selectedAcademicPeriod];
        }
        $examinationOptions = $this
            ->find('list')
            ->where($where)
            ->toArray();
        // POCOR-8919 end
        return $examinationOptions;
    }

    public function getGradingTypeOptions()
    {
        $examinationGradingType = TableRegistry::getTableLocator()->get('Examination.ExaminationGradingTypes');
        $examinationGradingTypeOptions = $examinationGradingType->find('list')->toArray();
        return $examinationGradingTypeOptions;
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->ExaminationSubjects->getAlias(), $this->ExaminationCentreRooms->getAlias()
        ];
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'maximum_award_amount') {
            return __('Annual Award Amount');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'registration_start_date') {
            return __('Registration Start Date');
        }  elseif ($field == 'registration_end_date') {
            return __('Registration End Date');
        } elseif ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'education_grade_id') {
            return __('Education Grade');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'description') {
            return __('Description');
        }elseif ($field == 'description') {
            return __('Description');
        }elseif ($field == 'education_programme_id') {
            return __('Education Programme');
        }elseif ($field == 'education_grade_id') {
            return __('Education Grade');
        }elseif ($field == 'examination_subjects') {
            return __('Examination Subjects');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
