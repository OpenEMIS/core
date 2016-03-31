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
		$this->hasMany('InstitutionAssessments', ['className' => 'Institution.InstitutionAssessments', 'dependent' => true]);

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
			'type' => 'select',
			'options' => $this->getSelectOptions($this->aliasField('types'))
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
			'fields' => $this->AssessmentPeriods->fields,
			'formFields' => array_keys($this->AssessmentPeriods->getFormFields($this->action))
		]);
		$this->field('subject_section', ['type' => 'section', 'title' => __('Subjects'), 'visible' => ['edit'=>true, 'add'=>true]]);
		$this->field('period_section', ['type' => 'section', 'title' => __('Periods'), 'visible' => ['edit'=>true, 'add'=>true]]);
	}

	// public function afterSave(Event $event, Entity $entity, ArrayObject $options, ArrayObject $extra) {
	// 	// after add redirect to edit
	// 	// if ($entity->isNew()) {
	// 	// 	$url = $this->url('edit');
	// 	// 	$url[1] = $entity->{$this->primaryKey()};
	// 	// 	$event->stopPropagation();
	// 	// 	return $this->controller->redirect($url);
	// 	// }
	// }


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
		list($programmeOptions, $selectedProgramme, $gradeOptions, $selectedGrade, $academicPeriodOptions, $selectedAcademicPeriod) = array_values($this->_getSelectOptions($entity));

		$this->field('education_programme_id', [
			'options' => $programmeOptions,
		]);
		$this->field('education_grade_id', [
			'options' => $gradeOptions
		]);
		$this->field('academic_period_id', [
			'options' => $academicPeriodOptions
		]);

		$this->setFieldOrder([
			'code', 'name', 'description', 'type', 'subject_section', 'education_programme_id', 'education_grade_id', 'assessment_items', 'period_section', 'academic_period_id', 'assessment_periods',
		]);

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
** addEdit action methods
**
******************************************************************************************************************/
	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		$this->_setGenericPatchOptions($patchOptions, true);
	}

	public function addEditOnChangeProgramme(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		if (empty($requestData[$this->alias()]['education_programme_id'])) {
			$requestData[$this->alias()]['education_grade_id'] = '';
			$requestData[$this->alias()]['assessment_items'] = [];
		}
		$this->_setGenericPatchOptions($patchOptions);
	}

	public function addEditOnChangeGrade(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		$extra['items_patched'] = true;
		$requestData[$this->alias()]['assessment_items'] = $this->AssessmentItems->populateAssessmentItemsArray($entity, $requestData[$this->alias()]['education_grade_id']);
		$this->_setGenericPatchOptions($patchOptions);
	}

	public function addEditOnNewAssessmentPeriod(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		$extra['periods_patched'] = true;
		$assessmentPeriods = [];
		if (array_key_exists($this->alias(), $requestData)) {
			if (array_key_exists('assessment_periods', $requestData[$this->alias()])) {
				$assessmentPeriods = $requestData[$this->alias()]['assessment_periods'];
			}
		}
		$requestData[$this->alias()]['assessment_periods'] = $this->AssessmentPeriods->appendAssessmentPeriodsArray($entity, $assessmentPeriods);
		$this->_setGenericPatchOptions($patchOptions);
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		list($programmeOptions, $selectedProgramme, $gradeOptions, $selectedGrade, $academicPeriodOptions, $selectedAcademicPeriod) = array_values($this->_getSelectOptions());

		$this->field('education_programme_id', [
			'options' => $programmeOptions,
			'value' => $selectedProgramme,
			'attr' => [
				'ng-ca-on-change' => true,
				'ca-on-change-source-url' => 'education-educationgrades.json?_finder=visible,list&education_programme_id=',
				'ca-on-change-target' => 'education_programme_target',
			]
		]);
		$this->field('education_grade_id', [
			'options' => $gradeOptions,
			'value' => $selectedGrade,
			'attr' => [
				'ca-id' => 'education_programme_target',
			]			
		]);
		$this->field('academic_period_id', [
			'options' => $academicPeriodOptions,
			'value' => $selectedAcademicPeriod
		]);

		$this->setFieldOrder([
			'code', 'name', 'description', 'type', 'subject_section', 'education_programme_id', 'education_grade_id', 'assessment_items', 'period_section', 'academic_period_id', 'assessment_periods',
		]);

		if (!array_key_exists('items_patched', $extra)) {
			if (!empty($selectedGrade)) {
				$assessmentItems = $this->AssessmentItems->populateAssessmentItemsArray($entity, $selectedGrade);
				$entity->assessment_items = $this->AssessmentItems->newEntities($assessmentItems, ['validate'=>false]);
			} else {
				$entity->assessment_items = [];
			}
		}
	}


/******************************************************************************************************************
**
** specific field methods
**
******************************************************************************************************************/

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request) {
		// $attr['onChangeReload'] = 'changeProgramme';
		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$attr['onChangeReload'] = 'changeGrade';
		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		$attr['attr'] = ['onchange' => "updateDates();"];
		return $attr;
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	private function _setGenericPatchOptions(ArrayObject $patchOptions, $validate = false) {
		$newOptions = [];
		if (!$validate) {
			$newOptions['associated'] = [
				'AssessmentItems' => ['validate' => false],
				'AssessmentPeriods' => ['validate' => false]
			];
		} else {
			if (is_bool($validate)) {
				$newOptions['associated'] = [
					'AssessmentItems', 'AssessmentPeriods'
				];
			} else {
				$newOptions['associated'] = [
					'AssessmentItems' => ['validate' => $validate],
					'AssessmentPeriods' => ['validate' => $validate]
				];
			}
		}
		$arrayOptions = $patchOptions->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$patchOptions->exchangeArray($arrayOptions);
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
