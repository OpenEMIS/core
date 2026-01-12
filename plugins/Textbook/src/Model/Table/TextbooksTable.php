<?php
namespace Textbook\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\View\Helper\UrlHelper;
use Cake\I18n\Time;

use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Http\ServerRequest;

class TextbooksTable extends ControllerActionTable {
    use MessagesTrait;
    use HtmlTrait;
    use OptionsTrait;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',     ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades',     ['className' => 'Education.EducationGrades']);
        $this->belongsTo('EducationSubjects',   ['className' => 'Education.EducationSubjects']);

        $this->belongsTo('TextbookDimensions',   ['className' => 'Textbook.TextbookDimensions']); //POCOR-7362

        // $this->hasMany('TextbookDimensions', ['className' => 'Textbook.TextbookDimensions', 'foreignKey' => ['textbook_id', 'textbook_dimension_id'], 'dependent' => true, 'cascadeCallBack' => true]); //POCOR-7362

        $this->hasMany('InstitutionTextbooks', ['className' => 'Institution.InstitutionTextbooks', 'foreignKey' => ['textbook_id', 'academic_period_id'], 'dependent' => true, 'cascadeCallBack' => true]);

        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportTextbooks']);

        $this->EducationLevels = TableRegistry::getTableLocator()->get('Education.EducationLevels');
        $this->EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
            ]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $request = $this->request;

        //academic period filter
        $request = $this->request;
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($request->getQuery('period')));

        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noTextbooks')),
            'callable' => function($id) {
                return $this->find()
                    ->where([
                        $this->aliasField('academic_period_id') => $id
                    ])
                    ->count();
            }
        ]);
        $extra['selectedPeriod'] = $selectedPeriod;
        $data['periodOptions'] = $periodOptions;
        $data['selectedPeriod'] = $selectedPeriod;

        //education level filter
        $levelOptions = $this->EducationLevels->getEducationLevelOptions($selectedPeriod);

        if ($levelOptions) {
            $levelOptions = array(-1 => __('-- Select Education Level --')) + $levelOptions;
        }

        if ($request->getQuery('level')) {
            $selectedLevel =  $request->getQuery('level');
        } else {
            $selectedLevel = -1;
        }

        $this->advancedSelectOptions($levelOptions, $selectedLevel, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noProgrammes')),
            'callable' => function($id) {
                if ($id > 0) {
                    return count($this->EducationProgrammes->getEducationProgrammesList($id));
                } else { //for select all.
                    return true;
                }
            }
        ]);
        $extra['selectedLevel'] = $selectedLevel;
        $data['levelOptions'] = $levelOptions;
        $data['selectedLevel'] = $selectedLevel;

        // education programmes filter
        if ($selectedPeriod && $selectedLevel) {

            $programmeOptions = $this->EducationProgrammes->getEducationProgrammesList($selectedLevel);

            $programmeOptions = array(-1 => __('-- Please Select Education Programme --')) + $programmeOptions;

            if ($request->getQuery('programme')) {
                $selectedProgramme = $request->getQuery('programme');
            } else {
                $selectedProgramme = -1;
            }

            $this->advancedSelectOptions($programmeOptions, $selectedProgramme, [
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
                'callable' => function($id) use ($selectedPeriod) {

                    if ($id > 0) {
                        return count($this->EducationGrades->getEducationGradesByProgrammes($id));
                    } else {
                        return true;
                    }
                }
            ]);
            $extra['selectedProgramme'] = $selectedProgramme;
            $data['programmeOptions'] = $programmeOptions;
            $data['selectedProgramme'] = $selectedProgramme;
        }

        //education subjects filter
        if ($selectedPeriod && $selectedProgramme) {
            $gradeSubjects = $this->EducationGrades->find('GradeSubjectsByProgramme', ['education_programme_id' => $selectedProgramme])->all();
            $subjectOptions = [];
            foreach ($gradeSubjects as $grade) {
                foreach ($grade->education_subjects as $subject) {
                    $key = $grade->id . '-' . $subject->id;
                    $subjectOptions[$key] = $grade->name . ' - ' . $subject->name;
                }
            }

            $subjectOptions = array(-1 => __('-- All Education Subject --')) + $subjectOptions;

            if ($request->getQuery('subject')) {
                $selectedSubject = $request->getQuery('subject');
            } else {
                $selectedSubject = -1;
            }

            $extra['selectedSubject'] = $selectedSubject;
            $data['subjectOptions'] = $subjectOptions;
            $data['selectedSubject'] = $selectedSubject;
        }

        //build up the control filter
        $extra['elements']['control'] = [
            'name' => 'Textbook.controls',
            'data' => $data,
            'order' => 3
        ];

        //hide fields on the index page.
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('education_grade_id', ['visible' => false]);
        $this->field('education_subject_id', ['visible' => false]);
        $this->field('author', ['visible' => false]);
        $this->field('year_published', ['visible' => false]);
        $this->field('expiry_date', ['visible' => false]);
        $this->field('textbook_dimension_id', ['visible' => false]); //POCOR-7362

        $this->setFieldOrder([
            'code', 'title', 'ISBN', 'publisher'
        ]);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Textbooks','Textbooks');
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
        $hasSearchKey = $this->request->getSession()->read($this->getRegistryAlias().'.search.key');

        $conditions = [];

        if (!$hasSearchKey) {
            //filter
            if (isset($extra['selectedPeriod'])) {
                if ($extra['selectedPeriod']) {
                    $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
                }
            }

            if (isset($extra['selectedProgramme'])) {
                if ($extra['selectedProgramme']) {
                    $query->innerJoinWith('EducationGrades.EducationProgrammes');
                    // pr($query);
                    $conditions[] = 'EducationProgrammes.id = ' . $extra['selectedProgramme'];
                }
            }

            if (isset($extra['selectedGrade'])) {
                if ($extra['selectedGrade'] > 0) {
                    $conditions[] = $this->aliasField('education_grade_id = ') . $extra['selectedGrade'];
                }
            }

            if (isset($extra['selectedSubject'])) {
                if ($extra['selectedSubject'] && $extra['selectedSubject'] > 0) {
                    $gradeSubject = explode('-', $extra['selectedSubject']);
                    $conditions[] = $this->aliasField('education_grade_id = ') . $gradeSubject[0];
                    $conditions[] = $this->aliasField('education_subject_id = ') . $gradeSubject[1];
                }
            }

            $query->where([$conditions]);
        }

    }

    public function viewAfterAction(EventInterface $event, Entity $entity)
    {
        $this->setupFields($entity);
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'AcademicPeriods','EducationSubjects',
            'EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems' //POCOR-5035
        ]);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $entity->name = $entity->code . ' - ' . $entity->title;
    }

    public function onGetEducationLevelId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->education_grade->education_programme->education_cycle->education_level->system_level_name;//POCOR-5035
        }
    }

    public function onGetEducationProgrammeId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->education_grade->education_programme->cycle_programme_name;//POCOR-5035
        }
    }

    public function onGetEducationSubjectId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->education_subject->code_name;
        }
    }

    // POCOR-7362
    public function onGetTextbookDimensionId(EventInterface $event, Entity $entity)
    {
        $textbookdimensions = TableRegistry::getTableLocator()->get('Textbook.TextbookDimensions');
        $query = $textbookdimensions->find()
                ->select(['name'])
                ->where(function ($exp, $q) use ($entity) {
                    if ($entity->textbook_dimension_id !== null) {
                        return $exp->eq('id', $entity->textbook_dimension_id);
                    } else {
                        return $exp->isNull('id');
                    }
                })->enableHydration(false);


        $result = $query->toArray();
        if ($this->action == 'view') {
            return $result;
        }
    }
    // POCOR-7362

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->getQuery('period')));

            $attr['options'] = $periodOptions;
			$attr['onChangeReload'] = true;
			$attr['default'] = $selectedPeriod;
        } else if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
        }
        return $attr;
    }

    public function onUpdateFieldEducationLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {
				$AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
				if(!empty($this->request->getQuery('period')) && empty($request->getData($this->aliasField('academic_period_id')))) {
					$academicPeriodId = $this->request->getQuery('period');
				} else {
					$academicPeriodId = !is_null($this->request->getData($this->aliasField('academic_period_id'))) ? $this->request->getData($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();
				}
                $educationLevelOptions = $this->EducationLevels->getEducationLevelOptions($academicPeriodId);

                $attr['options'] = $educationLevelOptions;
                $attr['onChangeReload'] = 'changeEducationLevel';

            } else if ($action == 'edit') {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $attr['entity']->education_grade->education_programme->education_cycle->education_level->system_level_name;//POCOR-5035
                $attr['value'] = $attr['entity']->education_grade->education_programme->education_cycle->education_level->id;//POCOR-5035
                // pr($attr['entity']);

            }
        }
        return $attr;
    }

    public function addEditOnChangeEducationLevel(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->getQuery['programme'] = -1;
        $request->getQuery['grade'] = -1;
        $request->getQuery['subject'] = -1;

        if ($request->is(['post', 'put'])) {
            $data = $request->getData();  // POCOR-8697
            
            if (is_array($data) && array_key_exists($this->getAlias(), $data)) {
                if (array_key_exists('education_level_id', $data[$this->getAlias()])) {
                    $request = $request->withQueryParams(['level' => $data[$this->getAlias()]['education_level_id']]);
                }
            }
        }
         
    }

    public function onUpdateFieldEducationProgrammeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {
                // $selectedLevel = $this->request->getQuery('level'); //POCOR-7485
                $selectedLevel = $this->request->getData('Textbooks')['education_level_id'];

                $programmeOptions = [];
                if ($selectedLevel) {
                    $programmeOptions = $this->EducationProgrammes->getEducationProgrammesList($selectedLevel);
                }
                $attr['options'] = $programmeOptions;
                $attr['onChangeReload'] = 'changeEducationProgramme';

            } else if ($action == 'edit') {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $attr['entity']->education_grade->education_programme->cycle_programme_name;//POCOR-5035
                $attr['value'] = $attr['entity']->education_grade->education_programme->id;//POCOR-5035
            }

        }
        return $attr;
    }

    public function addEditOnChangeEducationProgramme(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->getQuery['grade'] = -1;
        $request->getQuery['subject'] = -1;

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

                // $selectedProgramme = $request->getQuery('programme'); //POCOR-7485
                $selectedProgramme = $request->getData('Textbooks')['education_programme_id'];
                $gradeOptions = [];
                if ($selectedProgramme) {
                    $gradeOptions = $this->EducationGrades->getEducationGradesByProgrammes($selectedProgramme);
                }

                $attr['options'] = $gradeOptions;
                $attr['onChangeReload'] = 'changeEducationGrade';

            } else {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $attr['entity']->education_grade->name;//POCOR-5035
                $attr['value'] = $attr['entity']->education_grade->id; //POCOR-5035

            }
        }

        return $attr;
    }

    public function addEditOnChangeEducationGrade(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->getQuery['subject'] = -1;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('education_programme_id', $request->getData()[$this->getAlias()])) {
                    $request->getQuery['programme'] = $request->getData()[$this->getAlias()]['education_programme_id'];
                }

                if (array_key_exists('education_grade_id', $request->getData()[$this->getAlias()])) {
                    $request->getQuery['grade'] = $request->getData()[$this->getAlias()]['education_grade_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationSubjectId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                // $selectedGrade = $request->getQuery('grade'); //POCOR-7485
                $selectedGrade = $request->getData('Textbooks')['education_grade_id'];
                $subjectOptions = [];
                if ($selectedGrade) {
                    $subjectOptions = $this->EducationSubjects->getEducationSubjectsByGrades($selectedGrade);
                }

                $attr['options'] = $subjectOptions;

            } else {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $attr['entity']->education_subject->code_name;
                $attr['value'] = $attr['entity']->education_subject->id;

            }
        }

        return $attr;
    }

    public function onUpdateFieldCode(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $attr['entity']->code;
            $attr['value'] = $attr['entity']->code;

        }

        return $attr;
    }

    public function onUpdateFieldExpiryDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['default_date'] = false;
        return $attr;
    }

    public function onUpdateFieldYearPublished(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $lowestYear = $ConfigItems->value('lowest_year');

        $lowestYear = $ConfigItems->value('lowest_year') ?? '1950';

        if ($action == 'add' || $action == 'edit') {
            $now = Time::now();
            for ($i = $now->year; $i >= $lowestYear; $i--) {
                $yearOptions[$i] = $i;
            }

            $attr['options'] = $yearOptions;
        }

        return $attr;
    }
    

    // POCOR-7362

    public function onUpdateFieldTextbookDimensionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
         $textbookdimensions = TableRegistry::getTableLocator()->get('Textbook.TextbookDimensions');
         if ($action == 'add' || $action == 'edit') {
         $dimension = $textbookdimensions->find('list')->toArray();

         $attr['options'] = $dimension;
         }

         return $attr;

    }

    // POCOR-7362 ends

    public function setupFields(Entity $entity)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'select' => false,
            'entity' => $entity
        ]);
        $this->field('education_level_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('education_programme_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('education_grade_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('education_subject_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('code', [
            'entity' => $entity
        ]);
        $this->field('year_published', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('expiry_date', [
            'entity' => $entity
        ]);

        // POCOR-7362 add textbook dimension

        $this->field('textbook_dimension_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        // POCOR-7362 ends

        $this->setFieldOrder([
            'academic_period_id', 'education_level_id', 'education_programme_id', 'education_grade_id', 'education_subject_id',
            'code', 'title', 'author', 'publisher' , 'year_published', 'textbook_dimension_id', 'ISBN', 'expiry_date'
        ]);
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public function getTextbookOptions($academicPeriodId, $educationGradeId, $educationSubjectId)
    {
        $todayDate = Time::now()->format('Y-m-d');

        return  $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_title'])
                ->where([
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('education_grade_id') => $educationGradeId,
                    $this->aliasField('education_subject_id') => $educationSubjectId,
                    'OR' => [
                        [$this->aliasField('expiry_date') .' IS NULL'],
                        [$this->aliasField('expiry_date') .' > ' . "'$todayDate'"]
                    ]
                ])
                ->order([$this->aliasField('code') => 'ASC'])
                ->toArray();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'textbook_dimension_id') {
            return __('Dimension');
        } elseif ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'education_level_id') {
            return __('Education Level');
        } elseif ($field == 'education_subject_id') {
            return __('Education Subject');
        } elseif ($field == 'title') {
            return __('Title');
        } elseif ($field == 'author') {
            return __('Auther');
        } elseif ($field == 'publisher') {
            return __('Publisher');
        } elseif ($field == 'year_published') {
            return __('Year Published');
        } elseif ($field == 'ISBN') {
            return __('ISBN');
        } elseif ($field == 'expiry_date') {
            return __('Expiry Date');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'education_programme_id') {
            return __('Education Programme');
        } elseif ($field == 'education_grade_id') {
            return __('Education Grade');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        }elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'modified') {
            return __('Modified On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
