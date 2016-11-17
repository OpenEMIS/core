<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\View\Helper\UrlHelper;

use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class AssessmentsTable extends ControllerActionTable {
    use MessagesTrait;
    use HtmlTrait;
    use OptionsTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->belongsToMany('GradingTypes', [
            'className' => 'Assessment.AssessmentGradingTypes',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => 'assessment_id',
            'targetForeignKey' => 'assessment_grading_type_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('AssessmentPeriods', [
            'className' => 'Assessment.AssessmentPeriods',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => 'assessment_id',
            'targetForeignKey' => 'assessment_period_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'view']
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
            ->requirePresence('assessment_items')
            ->add('education_grade_id', [
                'ruleAssessmentExistByGradeAcademicPeriod' => [ //validate so only 1 assessment for each grade per academic period
                    'rule' => ['assessmentExistByGradeAcademicPeriod'],
                    'on' => function ($context) {
                        return $this->action == 'add';
                    }
                ]
            ]);
    }


    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

        $extra['selectedPeriod'] = $selectedPeriod;
        $extra['elements']['control'] = [
            'name' => 'Assessment.controls',
            'data' => [
                'periodOptions'=> $periodOptions,
                'selectedPeriod'=> $selectedPeriod
            ],
            'order' => 3
        ];

        $this->field('type', [
            'visible' => false
        ]);

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedPeriod']]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AssessmentItems.EducationSubjects']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $assessmentItems = $entity->assessment_items;

        //this is to sort array based on certain value on subarray, in this case based on education order value
        usort($assessmentItems, function($a,$b){ return $a['education_subject']['order']-$b['education_subject']['order'];} );

        $entity->assessment_items = $assessmentItems;

        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->action == 'edit') {
            $savedAssessmentItems = $entity->assessment_items;

            //check againts latest education setup whether there is any changes in grade sujects compared to the time when the assessment was setup.
            $currentAssessmentItems = $this->AssessmentItems->populateAssessmentItemsArray($entity->education_grade_id);
            $comparedAssessmentItems = $this->compareAssessmentItems($savedAssessmentItems, $currentAssessmentItems);

            //this is to sort array based on certain value on subarray, in this case based on education order value
            usort($comparedAssessmentItems, function($a,$b){ return $a['education_subject']['order']-$b['education_subject']['order'];} );

            $entity->assessment_items = $comparedAssessmentItems;
        }

        $this->setupFields($entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        //patch data to handle fail save because of validation error.
        if (array_key_exists($this->alias(), $requestData)) {
            if (array_key_exists('assessment_items', $requestData[$this->alias()])) {
                $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
                foreach ($requestData[$this->alias()]['assessment_items'] as $key => $item) {
                    $subjectId = $item['education_subject_id'];
                    $requestData[$this->alias()]['assessment_items'][$key]['education_subject'] = $EducationSubjects->get($subjectId);
                }
            } else { //logic to capture error if no subject inside the grade.
                $errorMessage = $this->aliasField('noSubjects');
                $requestData['errorMessage'] = $errorMessage;
            }
        }
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $errors = $entity->errors();
        if (!empty($errors)) {
            if (isset($requestData['errorMessage']) && !empty($requestData['errorMessage'])) {
                $this->Alert->error($requestData['errorMessage'], ['reset'=>true]);
            }
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
        $AssessmentItemsGradingTypes = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');
        $AssessmentItemsResults = TableRegistry::get('Assessment.AssessmentItemResults');

        $errors = $entity->errors();
        if (!$errors) {
            //during edit, before the remain and new subject updated, need to process the deleted subject first.
            if ($entity->has('assessment_items')) {
                foreach ($entity->assessment_items as $key => $value) {
                    if ($value->status == 'deleted'){ //for deleted subject
                        //remove from assessment items
                        $this->AssessmentItems->deleteAll(['id' => $value->id]);

                        //remove from assessment items grading types
                        $AssessmentItemsGradingTypes->deleteAll([
                            'education_subject_id' => $value->education_subject_id,
                            'assessment_id' => $entity->id
                        ]);

                        //remove assessment results
                        $AssessmentItemsResults->deleteAll([
                            'assessment_id' => $entity->id,
                            'education_subject_id' => $value->education_subject_id
                        ]);

                        //unset from entity so it wont be saved
                        unset($entity->assessment_items[$key]);
                    }
                }

                //check the period which ties to the assessment id, then loop and re-insert the newly added subject.
                $assessmentPeriods = $AssessmentPeriods
                                    ->find()
                                    ->select([
                                        'id' => $AssessmentPeriods->aliasField('id')
                                    ])
                                    ->where([
                                        $AssessmentPeriods->aliasField('assessment_id') => $entity->id
                                    ])
                                    ->toArray();
                
                $defaultGradingType = $this->GradingTypes->find()->first()->id; //get the first record of grading type as default value.

                foreach ($assessmentPeriods as $key => $index) { //loop through period.
                    foreach ($entity->assessment_items as $key1 => $value) { 

                        if ($value->status == 'new') { //loop through newly added subject
                            $newEntity = $AssessmentItemsGradingTypes->newEntity([
                                'assessment_id' => $entity->id,
                                'education_subject_id' => $value->education_subject_id,
                                'assessment_grading_type_id' => $defaultGradingType,
                                'assessment_period_id' => $assessmentPeriods[$key]->id
                            ]);
                            
                            $AssessmentItemsGradingTypes->save($newEntity); //insert new record
                        }
                    }
                }
            }
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [ //this will exclude checking during remove restrict
            $this->AssessmentItems->alias(),
            $this->GradingTypes->alias()
        ];
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $attr['options'] = $periodOptions;
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

            if ($action == 'add') {
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('visible')
                    ->contain(['EducationCycles'])
                    ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
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
        unset($data['Assessments']['assessment_items']);
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

    public function addEditOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['grade']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $selectedGrade = $request->data[$this->alias()]['education_grade_id'];
                    $request->query['grade'] = $selectedGrade;

                    $assessmentItems = $this->AssessmentItems->populateAssessmentItemsArray($selectedGrade);
                    $data[$this->alias()]['assessment_items'] = $assessmentItems;
                }
            }
        }
    }

    public function setupFields(Entity $entity)
    {
        $this->field('type', [
            'type' => 'hidden',
            'value' => 2,
            'attr' => ['value' => 2]
        ]);
        $this->field('academic_period_id', [
            'type' => 'select',
            'select' => false,
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
        $this->field('assessment_items', [
            'type' => 'element',
            'element' => 'Assessment.assessment_items'
        ]);

        $this->setFieldOrder([
            'code', 'name', 'description', 'academic_period_id', 'education_programme_id', 'education_grade_id', 'assessment_items'
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

    public function compareAssessmentItems($savedAssessmentItems, $currentAssessmentItems)
    {
        $currentSubjects = [];
        foreach ($currentAssessmentItems as $key => $obj) {
            $currentSubjects[$obj['education_subject_id']]['status'] = 'new'; //mark all current subjects as new
            $currentSubjects[$obj['education_subject_id']]['index'] = $key;
        }
        
        $savedSubjects = [];
        foreach ($savedAssessmentItems as $key => $obj) {
            if (array_key_exists($obj->education_subject_id, $currentSubjects)) { 
                $savedSubjects[$obj->education_subject_id]['status'] = 'remain'; //if can find saved on the current grade subject set, then mark it as remain
                unset($currentSubjects[$obj->education_subject_id]); //unset current subject so it wont be added twice during combined process.
            } else {
                $savedSubjects[$obj->education_subject_id]['status'] = 'deleted'; //cannot find, that it means has been deleted.
            }
            $savedSubjects[$obj->education_subject_id]['index'] = $key;
        }
        
        $comparedSubjects = $savedSubjects + $currentSubjects; //combined the saved and current.

        //rebuild the assessment item
        $comparedAssessmentItems = [];
        foreach ($comparedSubjects as $key => $value) {
            if ($value['status'] == 'remain' || $value['status'] == 'deleted') {
                $comparedAssessmentItems[$key] = $savedAssessmentItems[$value['index']];
            } else if ($value['status'] == 'new') {
                $comparedAssessmentItems[$key] = new Entity($currentAssessmentItems[$value['index']]);
            }

            $comparedAssessmentItems[$key]->status = $value['status'];
        }

        return $comparedAssessmentItems;
    }
}
