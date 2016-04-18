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

	private $_contain = ['AssessmentItems'];

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		
		$this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true]);
		$this->hasMany('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods', 'dependent' => true]);

		// To add this when there is a filter for education grade
		// if ($this->behaviors()->has('Reorder')) {
		// 	$this->behaviors()->get('Reorder')->config([
		// 		'filter' => 'education_grade_id',
		// 	]);
		// }

        $this->addBehavior('OpenEmis.Section');
	}

/******************************************************************************************************************
**
** cakephp events
**
******************************************************************************************************************/
	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('type', [
			'type' => 'hidden',
			'value' => 2,
			'attr' => ['value' => 2]
		]);
		$this->field('id', ['type' => 'hidden']);
		$this->field('assessment_items', [
			'type' => 'element',
			'element' => 'Assessment.Assessments/assessment_items',
			'visible' => ['view'=>true, 'edit'=>true, 'add'=>true],
			'fields' => $this->AssessmentItems->fields,
			'formFields' => array_keys($this->AssessmentItems->getFormFields($this->action))
		]);
		$this->field('assessment_periods', [
			'type' => 'element',
			'element' => 'Assessment.Assessments/assessment_periods',
			'visible' => ['view'=>true, 'edit'=>true, 'add'=>true],
			'entity' => $this->AssessmentPeriods->newEntity(),
			'fields' => $this->AssessmentPeriods->fields,
			'formFields' => array_keys($this->AssessmentPeriods->getFormFields($this->action))
		]);
		$this->field('education_grade_id', [
			'type' => 'element',
			'element' => 'Assessment.Assessments/education_grades',
			'visible' => ['view'=>true, 'edit'=>true, 'add'=>true],
		]);
		$this->field('subject_section', ['type' => 'section', 'title' => __('Subjects'), 'visible' => ['edit'=>true, 'add'=>true]]);
		$this->field('period_section', ['type' => 'section', 'title' => __('Periods'), 'visible' => ['edit'=>true, 'add'=>true]]);
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'AssessmentItems.EducationSubjects',
			'AssessmentItems.GradingTypes',
			'AssessmentPeriods',
			'EducationGrades',
			'AcademicPeriods'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->setFieldOrder([
			'code', 'name', 'description', 'academic_period_id', 'education_grade_id', 'assessment_items', 'assessment_periods'
		]);
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editAfterQuery(Event $event, Entity $entity, ArrayObject $extra) {
		$this->_setupFields($entity);
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		$newPeriodIds = (new Collection($entity->assessment_periods))->extract('id')->toArray();
		$oldPeriodIds = (new Collection($entity->getOriginal('assessment_periods')))->extract('id')->toArray();
		$periodsToBeDeleted = array_diff($oldPeriodIds, $newPeriodIds);
		if (!empty($periodsToBeDeleted)) {
			$this->AssessmentPeriods->deleteAll([
				$this->AssessmentPeriods->aliasField($this->AssessmentPeriods->primaryKey()) . ' IN ' => $periodsToBeDeleted
			]);
		}

		$newItemIds = (new Collection($entity->assessment_items))->extract('id')->toArray();
		$oldItemIds = (new Collection($entity->getOriginal('assessment_items')))->extract('id')->toArray();
		$itemsToBeDeleted = array_diff($oldItemIds, $newItemIds);
		if (!empty($itemsToBeDeleted)) {
			$this->AssessmentItems->deleteAll([
				$this->AssessmentItems->aliasField($this->AssessmentItems->primaryKey()) . ' IN ' => $itemsToBeDeleted
			]);
		}
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->_setupFields($entity);
	}


/******************************************************************************************************************
**
** specific field methods
**
******************************************************************************************************************/

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request) {

		$attr['attr'] = [
			'kd-on-change-element' => true,
			'kd-on-change-source-url' => $request->base . '/restful/Education-EducationGrades.json?_finder=visible,list&education_programme_id=',
			'kd-on-change-target' => 'education_grade_id',
		];
		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {

		$attr['attr'] = [
			'assessment-academic-period' => true,
			'assessment-academic-period-details-url' => $request->base . '/restful/AcademicPeriod-AcademicPeriods/{%id%}.json',
		];
		return $attr;
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	private function _setupFields(Entity $entity) {
		list($programmeOptions, $selectedProgramme, $gradeOptions, $selectedGrade, $academicPeriodOptions, $selectedAcademicPeriod) = array_values($this->_getSelectOptions());

		$this->field('education_programme_id', [
			'options' => $programmeOptions,
			'value' => $selectedProgramme,
		]);
		$this->field('academic_period_id', [
			'options' => $academicPeriodOptions,
			'value' => $selectedAcademicPeriod
		]);

		$this->setFieldOrder([
			'code', 'name', 'description', 'type', 'subject_section', 'education_programme_id', 'education_grade_id', 'assessment_items', 'period_section', 'academic_period_id', 'assessment_periods',
		]);

		$assessmentPeriodsErrors = [];
		if (!empty($entity->assessment_periods)) {
			foreach ($entity->assessment_periods as $key => $item) {
				$errors = [];
				foreach ($item->errors() as $field => $messages) {
					$errors[$field] = implode('<br/>', $messages);
				}
				$assessmentPeriodsErrors[$key] = $errors;
			}
		}
		$assessmentItemsErrors = [];
		if (!empty($entity->assessment_items)) {
			foreach ($entity->assessment_items as $key => $item) {
				$errors = [];
				foreach ($item->errors() as $field => $messages) {
					$errors[$field] = implode('<br/>', $messages);
				}
				$assessmentItemsErrors[$key] = $errors;
			}
		}
		$this->controller->set('assessmentPeriodsErrors', $assessmentPeriodsErrors);
		$this->controller->set('assessmentItemsErrors', $assessmentItemsErrors);
	}

	private function _getSelectOptions($entity = null) {

		// Education Programmes
		$EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
		$programmeOptions = $EducationProgrammes
			->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
			->find('visible')
			->contain(['EducationCycles'])
			->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
			->toArray();
		if (!is_null($entity) && $this->request->is(['get'])) {
			$selectedProgramme = $entity->education_programme_id;
		} else {
			$selectedProgramme = $this->postString('education_programme_id');
		}
		// End
		
		// Education Grades
		if (!empty($selectedProgramme)) {
			$EducationGrades = $this->EducationGrades;
			$gradeOptions = $EducationGrades
				// ->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
				->find('list')
				->find('visible')
				->contain(['EducationProgrammes'])
				->where([$EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
				->order(['EducationProgrammes.order' => 'ASC', $EducationGrades->aliasField('order') => 'ASC'])
				->toArray();
			$selectedGrade = $this->postString('education_grade_id');
			$this->advancedSelectOptions($gradeOptions, $selectedGrade);
			if (empty($gradeOptions)) {
				$gradeOptions = ['' => __('-- Select --')];
				$selectedGrade = '';
			}
		} else {
			$gradeOptions = ['' => __('-- Select --')];
			$selectedGrade = '';
		}
		// End

		// Academic Periods
		$AcademicPeriods = $this->AcademicPeriods;
		$academicPeriodOptions = $AcademicPeriods->getYearList();
		$selectedAcademicPeriod = $this->postString('academic_period_id');
		// End

		return compact('programmeOptions', 'selectedProgramme', 'gradeOptions', 'selectedGrade', 'academicPeriodOptions', 'selectedAcademicPeriod');
	}

}
