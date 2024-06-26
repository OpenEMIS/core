<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;

class ExaminationsTable extends ControllerActionTable {
    public function initialize(array $config)
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

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
            ])
            ->add('registration_start_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ],
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'registration_end_date', false]
                ]
            ])
            ->add('registration_end_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            ])
            ->requirePresence('examination_subjects');
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['ExaminationSubjects.EducationSubjects', 'ExaminationSubjects.ExaminationGradingTypes']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where($where);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamsTab();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // send to ctp
        $this->controller->set('examinationGradingTypeOptions', $this->getGradingTypeOptions());

        $this->setupFields($entity);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // only can choose subject in add
        $subjects = [];
        if (!empty($this->request->data[$this->alias()]['education_grade_id'])) {
            $selectedGrade = $this->request->data[$this->alias()]['education_grade_id'];
            $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
            $subjects = $EducationSubjects->getEducationSubjectsByGrades($selectedGrade);
        }

        // send to ctp
        $this->controller->set('educationSubjectOptions', $subjects);
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data[$this->alias()]['examination_subjects']) || empty($data[$this->alias()]['examination_subjects'])) {
            $this->Alert->warning($this->aliasField('noExaminationSubjects'));
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        // used to do validation for examination item date
        if (array_key_exists('examination_subjects', $data)) {
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

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['ExaminationSubjects.StudentResults']);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data[$this->alias()]['examination_subjects']) || empty($data[$this->alias()]['examination_subjects'])) {
            $this->Alert->warning($this->aliasField('noExaminationSubjects'));
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $errors = $entity->errors();
        if (empty($errors)) {
            // manually delete hasMany Examination items
            $fieldKey = 'examination_subjects';
            if (!array_key_exists($fieldKey, $data[$this->alias()])) {
                $data[$this->alias()][$fieldKey] = [];
            }

            $savedExamItems = array_column($data[$this->alias()][$fieldKey], 'id');
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

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));
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

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['visible'] = false;

        } else if ($action == 'add' || $action == 'edit') {
			
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$academicPeriodId = !is_null($request->data($this->aliasField('academic_period_id'))) ? $request->data($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();

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

    public function addEditOnChangeEducationProgrammeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['programme']);
        unset($data[$this->alias()]['examination_subjects']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_programme_id', $request->data[$this->alias()])) {
                    $request->query['programme'] = $request->data[$this->alias()]['education_programme_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                $selectedProgramme = $request->query('programme');
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

    public function addOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['grade']);
        unset($data[$this->alias()]['examination_subjects']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $selectedGrade = $request->data[$this->alias()]['education_grade_id'];
                    $request->query['grade'] = $selectedGrade;
                }
            }
        }
    }

    public function addOnAddExaminationItem(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $fieldKey = 'examination_subjects';

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if ($data->offsetExists($this->alias())) {
            $data[$this->alias()][$fieldKey][] = [
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

    public function getExaminationOptions($selectedAcademicPeriod)
    {
        $examinationOptions = $this
            ->find('list')
            ->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();

        return $examinationOptions;
    }

    public function getGradingTypeOptions()
    {
        $examinationGradingType = TableRegistry::get('Examination.ExaminationGradingTypes');
        $examinationGradingTypeOptions = $examinationGradingType->find('list')->toArray();
        return $examinationGradingTypeOptions;
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->ExaminationSubjects->alias(), $this->ExaminationCentreRooms->alias()
        ];
    }
}
