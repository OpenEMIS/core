<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Collection\Collection;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use Cake\View\Helper\UrlHelper;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class AssessmentsTable extends ControllerActionTable {
    use MessagesTrait;
    use HtmlTrait;
    use OptionsTrait;

    // private $_contain = ['AssessmentItems'];

    public function initialize(array $config) 
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        
        $this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AssessmentItemGradingTypes', ['className' => 'Assessment.AssessmentItemGradingTypes', 'dependent' => true, 'cascadeCallbacks' => true]);

        // $this->addBehavior('OpenEmis.Section');
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AssessmentItems.EducationSubjects']);
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
            }
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldEducationProgramme(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['visible'] = false;
        } else if ($action == 'add' || $action == 'edit') {
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
            $programmeOptions = $EducationProgrammes
                ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                ->find('visible')
                ->contain(['EducationCycles'])
                ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC']);

            if ($action == 'edit') { //since programme_id is not stored, then during edit need to get from grade
                $attr['default'] = $this->EducationGrades->get($attr['entity']->education_grade_id)->education_programme_id;
            }

            $attr['options'] = $programmeOptions;
            $attr['onChangeReload'] = 'changeEducationProgramme';
        }

        return $attr;
    }

    public function addEditOnChangeEducationProgramme(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra) 
    {
        $request = $this->request;
        unset($request->query['programme']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_programme', $request->data[$this->alias()])) {
                    $request->query['programme'] = $request->data[$this->alias()]['education_programme'];
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) 
    {
        if ($action == 'add' || $action == 'edit') {
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
                    /*
                    pr($this->alias());
                    $data[$this->alias()]['assessment_items'][0] = ['education_subject_id' => 1, 'weight' => 0.4];
                    $data[$this->alias()]['assessment_items'][1] = ['education_subject_id' => 2, 'weight' => 0.6];
                    pr($data[$this->alias()]);
                    */
                }
            }
        }
    }

    // public function onUpdateFieldAssessmentItems(Event $event, array $attr, $action, Request $request) 
    // {
    //     if ($action == 'add' || $action == 'edit') {
    //         $selectedGrade = $request->query('grade_id');
    //         $assessmentItems = [];
    //         if (!is_null($selectedGrade)) {
    //             $assessmentItems = $this->AssessmentItems->populateAssessmentItemsArray($selectedGrade);
    //         }
    //     }

    //     return $attr;
        // if (array_key_exists('grade', $request->query)) {
        //     $selectedGrade = $request->query['grade'];

        //     $attr['data'] = $this->AssessmentItems->populateAssessmentItemsArray($selectedGrade);

            // $attr['data'] = $this->EducationGrades
            //     ->find('list')
            //     ->find('visible')
            //     ->contain(['EducationProgrammes'])
            //     ->where([$this->EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
            //     ->order(['EducationProgrammes.order' => 'ASC', $this->EducationGrades->aliasField('order') => 'ASC'])
            //     ->toArray();
            // $attr['onChangeReload'] = 'changeEducationGrade';
        //     return $attr;
        // }
    // }

    public function setupFields(Entity $entity) 
    {
        $this->field('type', [
            'type' => 'hidden',
            'value' => 2,
            'attr' => ['value' => 2]
        ]);
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('education_programme', [
            'type' => 'select',
            'entity' => $entity
        ]);  // virtual field so no _id behind
        $this->field('education_grade_id', ['type' => 'select']);
        $this->field('subjects', [
            'type' => 'element',
            'element' => 'Assessment.Assessments/assessment_items',
            // 'fields' => $this->AssessmentItems->fields,
            // 'formFields' => array_keys($this->AssessmentItems->getFormFields($this->action))
        ]);

        $this->setFieldOrder([
            'code', 'name', 'description', 'academic_period_id', 'education_programme', 'education_grade_id', 'subjects'
        ]);
        // $this->field('education_grade_id', ['type' => 'select']);
        // $this->field('start_time', ['type' => 'time']);
        // $this->field('end_time', ['type' => 'time']);
        // $this->field('location', [
        //     'after' => 'end_time', 
        //     'attr' => [
        //         'label' => $this->getMessage('InstitutionShifts.occupier')
        //     ]
        // ]);
        // $this->field('location_institution_id', ['after' => 'location']);
    }

// /******************************************************************************************************************
// **
// ** cakephp events
// **
// ******************************************************************************************************************/
//     public function beforeAction(Event $event, ArrayObject $extra) {
//         $this->field('type', [
//             'type' => 'hidden',
//             'value' => 2,
//             'attr' => ['value' => 2]
//         ]);
//         $this->field('id', ['type' => 'hidden']);
//         $this->field('assessment_items', [
//             'type' => 'element',
//             'element' => 'Assessment.Assessments/assessment_items',
//             'visible' => ['view'=>true, 'edit'=>true, 'add'=>true],
//             'fields' => $this->AssessmentItems->fields,
//             'formFields' => array_keys($this->AssessmentItems->getFormFields($this->action))
//         ]);
//         $this->field('assessment_periods', [
//             'type' => 'element',
//             'element' => 'Assessment.Assessments/assessment_periods',
//             'visible' => ['view'=>true, 'edit'=>true, 'add'=>true],
//             'entity' => $this->AssessmentPeriods->newEntity(),
//             'fields' => $this->AssessmentPeriods->fields,
//             'formFields' => array_keys($this->AssessmentPeriods->getFormFields($this->action))
//         ]);
//         $this->field('education_grade_id', [
//             'type' => 'element',
//             'element' => 'Assessment.Assessments/education_grades',
//             'visible' => ['view'=>true, 'edit'=>true, 'add'=>true],
//         ]);
//         $this->field('subject_section', ['type' => 'section', 'title' => __('Subjects'), 'visible' => ['edit'=>true, 'add'=>true]]);
//         $this->field('period_section', ['type' => 'section', 'title' => __('Periods'), 'visible' => ['edit'=>true, 'add'=>true]]);
//     }


// /******************************************************************************************************************
// **
// ** view action methods
// **
// ******************************************************************************************************************/
//     public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
//         $contain = [
//             'AssessmentItems.EducationSubjects',
//             'AssessmentPeriods',
//             'EducationGrades',
//             'AcademicPeriods'
//         ];

//         if ($this->action == 'view') {
//             $contain[] = 'AssessmentItems.GradingTypes';
//         }
//         $query->contain($contain);
//     }

//     public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
//         $this->setFieldOrder([
//             'code', 'name', 'description', 'academic_period_id', 'education_grade_id', 'assessment_items', 'assessment_periods'
//         ]);
//     }


// /******************************************************************************************************************
// **
// ** edit action methods
// **
// ******************************************************************************************************************/
//     public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
//         $newPeriodIds = (new Collection($entity->assessment_periods))->extract('id')->toArray();
//         $oldPeriodIds = (new Collection($entity->getOriginal('assessment_periods')))->extract('id')->toArray();
//         $periodsToBeDeleted = array_diff($oldPeriodIds, $newPeriodIds);
//         if (!empty($periodsToBeDeleted)) {
//             $this->AssessmentPeriods->deleteAll([
//                 $this->AssessmentPeriods->aliasField($this->AssessmentPeriods->primaryKey()) . ' IN ' => $periodsToBeDeleted
//             ]);
//         }

//         $newItemIds = (new Collection($entity->assessment_items))->extract('id')->toArray();
//         $oldItemIds = (new Collection($entity->getOriginal('assessment_items')))->extract('id')->toArray();
//         $itemsToBeDeleted = array_diff($oldItemIds, $newItemIds);
//         if (!empty($itemsToBeDeleted)) {
//             $this->AssessmentItems->deleteAll([
//                 $this->AssessmentItems->aliasField($this->AssessmentItems->primaryKey()) . ' IN ' => $itemsToBeDeleted
//             ]);
//         }
//     }


// /******************************************************************************************************************
// **
// ** addEdit action methods
// **
// ******************************************************************************************************************/
//     public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
//         $this->_setupFields($entity);
//     }

//     public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
//         $data = $requestData[$this->alias()];
//         if (!empty($data['id'])) {
//             foreach ($data['assessment_periods'] as $key => $value) {
//                 $requestData[$this->alias()]['assessment_periods'][$key]['assessment_id'] = $data['id'];
//             }
//         }
//     }


// /******************************************************************************************************************
// **
// ** specific field methods
// **
// ******************************************************************************************************************/

//     public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request) {

//         $attr['attr'] = [
//             'kd-on-change-element' => true,
//             'kd-on-change-source-url' => $request->base . '/restful/Education-EducationGrades.json?_finder=visible,list&education_programme_id=',
//             'kd-on-change-target' => 'education_grade_id',
//         ];
//         return $attr;
//     }

//     public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {

//         $attr['attr'] = [
//             'assessment-academic-period' => true,
//             'assessment-academic-period-details-url' => $request->base . '/restful/AcademicPeriod-AcademicPeriods/{%id%}.json',
//         ];
//         return $attr;
//     }


// /******************************************************************************************************************
// **
// ** essential methods
// **
// ******************************************************************************************************************/
//     private function _setupFields(Entity $entity) {
//         list($programmeOptions, $selectedProgramme, $gradeOptions, $selectedGrade, $academicPeriodOptions, $selectedAcademicPeriod) = array_values($this->_getSelectOptions());

//         $this->field('education_programme_id', [
//             'options' => $programmeOptions,
//             'value' => $selectedProgramme,
//         ]);
//         $this->field('academic_period_id', [
//             'options' => $academicPeriodOptions,
//             'value' => $selectedAcademicPeriod
//         ]);

//         $this->setFieldOrder([
//             'code', 'name', 'description', 'type', 'subject_section', 'education_programme_id', 'education_grade_id', 'assessment_items', 'period_section', 'academic_period_id', 'assessment_periods',
//         ]);

//         $assessmentPeriodsErrors = [];
//         $assessmentPeriodsData = [];
//         if (!empty($entity->assessment_periods)) {
//             foreach ($entity->assessment_periods as $key => $item) {
//                 $attributes = $item->toArray();
//                 $assessmentPeriodsData[$key] = array_merge($attributes, $item->invalid());
//                 $errors = [];
//                 foreach ($item->errors() as $field => $messages) {
//                     $errors[$field] = implode('<br/>', $messages);
//                 }
//                 $assessmentPeriodsErrors[$key] = $errors;
//             }
//         }
//         $assessmentItemsErrors = [];
//         $assessmentItemsData = [];
//         if (!empty($entity->assessment_items)) {
//             foreach ($entity->assessment_items as $key => $item) {
//                 $attributes = $item->toArray();
//                 $assessmentItemsData[$key] = array_merge($attributes, $item->invalid());
//                 $errors = [];
//                 foreach ($item->errors() as $field => $messages) {
//                     $errors[$field] = implode('<br/>', $messages);
//                 }
//                 $assessmentItemsErrors[$key] = $errors;
//             }
//         }
//         $this->controller->set('assessmentPeriodsErrors', $assessmentPeriodsErrors);
//         $this->controller->set('assessmentItemsErrors', $assessmentItemsErrors);
//         $this->controller->set('assessmentPeriodsData', $assessmentPeriodsData);
//         $this->controller->set('assessmentItemsData', $assessmentItemsData);
//     }

//     private function _getSelectOptions($entity = null) {

//         // Education Programmes
//         $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
//         $programmeOptions = $EducationProgrammes
//             ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
//             ->find('visible')
//             ->contain(['EducationCycles'])
//             ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
//             ->toArray();
//         if (!is_null($entity) && $this->request->is(['get'])) {
//             $selectedProgramme = $entity->education_programme_id;
//         } else {
//             $selectedProgramme = $this->postString('education_programme_id');
//         }
//         // End
        
//         // Education Grades
//         if (!empty($selectedProgramme)) {
//             $EducationGrades = $this->EducationGrades;
//             $gradeOptions = $EducationGrades
//                 // ->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
//                 ->find('list')
//                 ->find('visible')
//                 ->contain(['EducationProgrammes'])
//                 ->where([$EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
//                 ->order(['EducationProgrammes.order' => 'ASC', $EducationGrades->aliasField('order') => 'ASC'])
//                 ->toArray();
//             $selectedGrade = $this->postString('education_grade_id');
//             $this->advancedSelectOptions($gradeOptions, $selectedGrade);
//             if (empty($gradeOptions)) {
//                 $gradeOptions = ['' => __('-- Select --')];
//                 $selectedGrade = '';
//             }
//         } else {
//             $gradeOptions = ['' => __('-- Select --')];
//             $selectedGrade = '';
//         }
//         // End

//         // Academic Periods
//         $AcademicPeriods = $this->AcademicPeriods;
//         $academicPeriodOptions = $AcademicPeriods->getYearList();
//         $selectedAcademicPeriod = $this->postString('academic_period_id');
//         // End

//         return compact('programmeOptions', 'selectedProgramme', 'gradeOptions', 'selectedGrade', 'academicPeriodOptions', 'selectedAcademicPeriod');
//     }
}
