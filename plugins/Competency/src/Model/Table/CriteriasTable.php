<?php
namespace Competency\Model\Table;

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

class CriteriasTable extends ControllerActionTable {
    use MessagesTrait;
    use HtmlTrait;
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('competency_criterias');

        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Items',           ['className' => 'Competency.Items', 'foreignKey' => ['competency_item_id', 'academic_period_id']]);
        $this->belongsTo('GradingTypes',    ['className' => 'Competency.GradingTypes', 'foreignKey' => 'competency_grading_type_id']);

        $this->Templates = TableRegistry::get('Competency.Templates');
        
        // $this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true, 'cascadeCallbacks' => true]);

        // $this->belongsToMany('GradingTypes', [
        //     'className' => 'Assessment.AssessmentGradingTypes',
        //     'joinTable' => 'assessment_items_grading_types',
        //     'foreignKey' => 'assessment_id',
        //     'targetForeignKey' => 'assessment_grading_type_id',
        //     'through' => 'Assessment.AssessmentItemsGradingTypes',
        //     'dependent' => true,
        //     'cascadeCallbacks' => true
        // ]);

        // $this->belongsToMany('AssessmentPeriods', [
        //     'className' => 'Assessment.AssessmentPeriods',
        //     'joinTable' => 'assessment_items_grading_types',
        //     'foreignKey' => 'assessment_id',
        //     'targetForeignKey' => 'assessment_period_id',
        //     'through' => 'Assessment.AssessmentItemsGradingTypes',
        //     'dependent' => true,
        //     'cascadeCallbacks' => true
        // ]);

        // $this->addBehavior('Restful.RestfulAccessControl', [
        //     'Results' => ['index', 'view']
        // ]);
        // $this->setDeleteStrategy('restrict');
    }

    // public function validationDefault(Validator $validator) {
    //     $validator = parent::validationDefault($validator);

    //     return $validator
    //         ->add('code', [
    //             'ruleUniqueCode' => [
    //                 'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
    //                 'provider' => 'table'
    //             ]
    //         // ])
    //         // ->requirePresence('assessment_items')
    //         // ->add('education_grade_id', [
    //         //     'ruleAssessmentExistByGradeAcademicPeriod' => [ //validate so only 1 assessment for each grade per academic period
    //         //         'rule' => ['assessmentExistByGradeAcademicPeriod'],
    //         //         'on' => function ($context) {
    //         //             return $this->action == 'add';
    //         //         }
    //         //     ]
    //         ]);
    // }


    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $request = $this->request;

        //academic period filter
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));
        $extra['selectedPeriod'] = $selectedPeriod;
        $data['periodOptions'] = $periodOptions;
        $data['selectedPeriod'] = $selectedPeriod;

        //template filter
        $templateOptions = $this->Templates->getTemplateByAcademicPeriod($selectedPeriod);

        if ($templateOptions) {
            $templateOptions = array(-1 => __('-- Select Template --')) + $templateOptions;
        }

        if ($request->query('template')) {
            $selectedTemplate = $request->query('template');
        } else {
            $selectedTemplate = -1;
        }

        $extra['selectedTemplate'] = $selectedTemplate;
        $data['templateOptions'] = $templateOptions;
        $data['selectedTemplate'] = $selectedTemplate;

        //item filter
        if ($selectedPeriod && $selectedTemplate) {

            $itemOptions = $this->Items->getItemByTemplateAcademicPeriod($selectedTemplate, $selectedPeriod);

            $itemOptions = array(-1 => __('-- Select Item --')) + $itemOptions;

            if ($request->query('item')) {
                $selectedItem = $request->query('item');
            } else {
                $selectedItem = -1;
            }

            $extra['selectedItem'] = $selectedItem;
            $data['itemOptions'] = $itemOptions;
            $data['selectedItem'] = $selectedItem;
        }
        
        $extra['elements']['control'] = [
            'name' => 'Competency.criterias_controls',
            'data' => $data,
            'order' => 3
        ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // $query->where([$this->aliasField('academic_period_id') => $extra['selectedPeriod']]);

        if (array_key_exists('selectedPeriod', $extra)) {
            if ($extra['selectedPeriod']) {
                $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
            }
        }

        if (array_key_exists('selectedItem', $extra)) {
            if ($extra['selectedItem']) {
                $conditions[] = $this->aliasField('competency_item_id = ') . $extra['selectedItem'];
            }
        }

        $query->where([$conditions]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Items.Templates']);
    }

    // // public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    // // {
    // //     $assessmentItems = $entity->assessment_items;

    // //     //this is to sort array based on certain value on subarray, in this case based on education order value
    // //     usort($assessmentItems, function($a,$b){ return $a['education_subject']['order']-$b['education_subject']['order'];} );

    // //     $entity->assessment_items = $assessmentItems;

    // //     $this->setupFields($entity);
    // // }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // if ($this->action == 'edit')
        // {
        //     $assessmentItems = $entity->assessment_items;

        //     //this is to sort array based on certain value on subarray, in this case based on education order value
        //     usort($assessmentItems, function($a,$b){ return $a['education_subject']['order']-$b['education_subject']['order'];} );

        //     $entity->assessment_items = $assessmentItems;
        // }

        $this->setupFields($entity);
    }

    // // public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    // // {
    // //     //patch data to handle fail save because of validation error.
    // //     if (array_key_exists($this->alias(), $requestData)) {
    // //         if (array_key_exists('assessment_items', $requestData[$this->alias()])) {
    // //             $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
    // //             foreach ($requestData[$this->alias()]['assessment_items'] as $key => $item) {
    // //                 $subjectId = $item['education_subject_id'];
    // //                 $requestData[$this->alias()]['assessment_items'][$key]['education_subject'] = $EducationSubjects->get($subjectId);
    // //             }
    // //         } else { //logic to capture error if no subject inside the grade.
    // //             $errorMessage = $this->aliasField('noSubjects');
    // //             $requestData['errorMessage'] = $errorMessage;
    // //         }
    // //     }

    // // }

    // // public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    // // {
    // //     // pr($entity);
    // //     $errors = $entity->errors();
    // //     if (!empty($errors)) {
    // //         if (isset($requestData['errorMessage']) && !empty($requestData['errorMessage'])) {
    // //             $this->Alert->error($requestData['errorMessage'], ['reset'=>true]);
    // //         }
    // //     }
    // // }

    // // public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    // // {
    // //     $extra['excludedModels'] = [ //this will exclude checking during remove restrict
    // //         $this->AssessmentItems->alias(),
    // //         $this->GradingTypes->alias()
    // //     ];
    // // }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

            if ($action == 'add') {
                $attr['default'] = $selectedPeriod;
                $attr['options'] = $periodOptions;
                $attr['onChangeReload'] = 'changeAcademicPeriod';
            } else if ($action == 'edit') {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $periodOptions[$attr['entity']->academic_period_id];
                $attr['value'] = $attr['entity']->academic_period_id;
            }
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['template'] = '-1';
        $request->query['item'] = '-1';
        
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyTemplateId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $templateOptions = $this->Templates->getTemplateByAcademicPeriod($selectedPeriod);
                // pr($templateOptions);

                $attr['options'] = $templateOptions;
                $attr['onChangeReload'] = 'changeCompetencyTemplate';
                // $attr['default'] = $selectedPeriod;

            } else {
                // pr($attr['entity']);die;
                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->item->competency_template_id;
                // $attr['attr']['value'] = $Templates->get([$attr['entity']->item->competency_template_id, $attr['entity']->academic_period_id])->code_name;
                $attr['attr']['value'] = $attr['entity']->item->template->code_name;

            }
        }
        return $attr;
    }

    public function addEditOnChangeCompetencyTemplate(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['item'] = '-1';

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }

                if (array_key_exists('competency_template_id', $request->data[$this->alias()])) {
                    $request->query['template'] = $request->data[$this->alias()]['competency_template_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyItemId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $selectedTemplate = $request->query('template');
                $itemOptions = [];
                if ($selectedTemplate) {
                    $itemOptions = $this->Items->getItemByTemplateAcademicPeriod($selectedTemplate, $selectedPeriod);
                }
                // pr($itemOptions);

                $attr['options'] = $itemOptions;
                // $attr['onChangeReload'] = 'changeCompetencyTemplate';
                // $attr['default'] = $selectedPeriod;

            } else {
                // pr($attr['entity']);
                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->competency_item_id;
                // $attr['attr']['value'] = $this->Templates->get([$attr['entity']->competency_template_id, $attr['entity']->academic_period_id])->code_name;
                $attr['attr']['value'] = $attr['entity']->item->name;

            }
        }
        return $attr;
    }

    // public function addEditOnChangeCompetencyTemplate(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    // {
    //     $request = $this->request;
    //     if ($request->is(['post', 'put'])) {
    //         if (array_key_exists($this->alias(), $request->data)) {
    //             if (array_key_exists('competency_template_id', $request->data[$this->alias()])) {
    //                 $request->query['template'] = $request->data[$this->alias()]['competency_template_id'];
    //             }
    //         }
    //     }
    // }

    // public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    // {
    //     if ($action == 'view') {
    //         $attr['visible'] = false;
    //     } else if ($action == 'add' || $action == 'edit') {

    //         $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

    //         if ($action == 'add') {
    //             $programmeOptions = $EducationProgrammes
    //                 ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
    //                 ->find('visible')
    //                 ->contain(['EducationCycles'])
    //                 ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
    //                 ->toArray();

    //             $attr['options'] = $programmeOptions;
    //             $attr['onChangeReload'] = 'changeEducationProgrammeId';

    //         } else {
    //             //since programme_id is not stored, then during edit need to get from grade
    //             $programmeId = $this->EducationGrades->get($attr['entity']->education_grade_id)->education_programme_id;
    //             $attr['type'] = 'readonly';
    //             $attr['value'] = $programmeId;
    //             $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
    //         }
    //     }
    //     return $attr;
    // }

    // public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    // {
    //     if ($action == 'add' || $action == 'edit') {

    //         if ($action == 'add') {

    //             $selectedProgramme = $request->query('programme');
    //             $gradeOptions = [];
    //             if (!is_null($selectedProgramme)) {
    //                 $gradeOptions = $this->EducationGrades
    //                     ->find('list')
    //                     ->find('visible')
    //                     ->contain(['EducationProgrammes'])
    //                     ->where([$this->EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
    //                     ->order(['EducationProgrammes.order' => 'ASC', $this->EducationGrades->aliasField('order') => 'ASC'])
    //                     ->toArray();
    //             }

    //             $attr['options'] = $gradeOptions;
    //             $attr['onChangeReload'] = 'changeEducationGrade';

    //         } else {

    //             $attr['type'] = 'readonly';
    //             $attr['attr']['value'] = $this->EducationGrades->get($attr['entity']->education_grade_id)->name;
    //         }
    //     }

    //     return $attr;
    // }

    // public function addEditOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    // {
    //     $request = $this->request;
    //     unset($request->query['grade']);

    //     if ($request->is(['post', 'put'])) {
    //         if (array_key_exists($this->alias(), $request->data)) {
    //             if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
    //                 $selectedGrade = $request->data[$this->alias()]['education_grade_id'];
    //                 $request->query['grade'] = $selectedGrade;

    //                 $assessmentItems = $this->AssessmentItems->populateAssessmentItemsArray($selectedGrade);
    //                 $data[$this->alias()]['assessment_items'] = $assessmentItems;
    //             }
    //         }
    //     }
    // }

    // public function onUpdateFieldMandatory(Event $event, array $attr, $action, Request $request)
    // {
    //     $attr['options'] = $this->getSelectOptions('general.yesno');
    //     // $attr['onChangeReload'] = 'changeCurrent';

    //     return $attr;
    // }

    public function setupFields(Entity $entity)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('competency_template_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('competency_item_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('name', [
            'type' => 'text',
            'entity' => $entity
        ]);
        $this->field('competency_grading_type_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        
        // $this->field('education_grade_id', [
        //     'type' => 'select',
        //     'entity' => $entity
        // ]);
        // $this->field('assessment_items', [
        //     'type' => 'element',
        //     'element' => 'Assessment.assessment_items'
        // ]);

        $this->setFieldOrder([
            'academic_period_id', 'competency_template_id', 'competency_item_id', 'name', 'competency_grading_type_id'
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
}
