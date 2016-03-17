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
		
		$this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
		// $this->hasMany('AssessmentStatuses', ['className' => 'Assessment.AssessmentStatuses', 'dependent' => true, 'cascadeCallbacks' => true]);
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
			// 'visible' => ['view'=>true, 'edit'=>true, 'add'=>true],
			'visible' => ['edit'=>true, 'add'=>true],
			'fields' => $this->AssessmentPeriods->fields,
			'formFields' => array_keys($this->AssessmentPeriods->getFormFields($this->action))
		]);
		$this->field('subject_section', ['type' => 'section', 'title' => __('Subjects'), 'visible' => ['edit'=>true, 'add'=>true]]);
		$this->field('period_section', ['type' => 'section', 'title' => __('Periods'), 'visible' => ['edit'=>true, 'add'=>true]]);
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options, ArrayObject $extra) {
		if ($entity->has('assessment_items')) {
			$originalIds = [];
			foreach ($entity->getOriginal('assessment_items') as $key => $value) {
				$originalIds[] = $value->id;
			}
			$currentIds = [];
			foreach ($entity->assessment_items as $key => $value) {
				$currentIds[] = $value->id;
			}

			$deleteIds = array_diff($originalIds, $currentIds);

			// have to reconfirm that all these ids do not have results before deletion
			if (!empty($deleteIds)) {
				$AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
				$validatedDeleteIds = [];
				foreach ($deleteIds as $key => $value) {
					$resultCount = $AssessmentItemResults->find()
						->where([$AssessmentItemResults->aliasField('assessment_item_id') .' = '. $value])
						->count();

					if (empty($resultCount)) {
						$validatedDeleteIds[] = $value;
					}
				}

				if (!empty($validatedDeleteIds)) {
					$this->AssessmentItems->deleteAll([
						$this->AssessmentItems->aliasField($this->AssessmentItems->primaryKey()).' IN ' => $validatedDeleteIds
					]);
				}
			}
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options, ArrayObject $extra) {
		// after add redirect to edit
		if ($entity->isNew()) {
			$url = $this->url('edit');
			$url[1] = $entity->{$this->primaryKey()};
			$event->stopPropagation();
			return $this->controller->redirect($url);
			
		}
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'AssessmentItems.EducationSubjects',
			'AssessmentItems.GradingTypes'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->setFieldOrder([
			'code', 'name', 'description', 'academic_period_id', 'education_grade_id', 'assessment_items'
		]);
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/

	public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra) {
		$selectedProgramme = $this->EducationGrades->get($entity->education_grade_id)->education_programme_id;
		$entity->education_programmes = $selectedProgramme;
		$this->request->query['programme'] = $selectedProgramme;
		$this->request->query['grade'] = $entity->education_grade_id;
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->fields['education_programmes']['type'] = 'readonly';
		$this->fields['education_programmes']['attr']['value'] = $this->fields['education_programmes']['options'][$entity->education_programmes]['text'];
		$this->fields['education_grade_id']['type'] = 'readonly';
		$this->fields['education_grade_id']['attr']['value'] = $this->fields['education_grade_id']['options'][$entity->education_grade_id]['text'];

	}


/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/
	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		// if ($this->action=='edit') {
		// 	$this->fields['visible']['visible'] = false;
		// }
		// $this->fields['grading_options']['formFields'] = array_keys($this->GradingOptions->getFormFields());

		// $this->setFieldOrder([
		// 	'code',  'name', 'grading_options',
		// ]);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra) {
		// if (!array_key_exists('education_subjects', $data[$this->alias()])) {
		// 	$data[$this->alias()]['education_subjects'] = [];
		// }

		// Required by patchEntity for associated data
		// $newOptions = [];
		// $newOptions['associated'] = $this->_contain;

		// $arrayOptions = $options->getArrayCopy();
		// $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		// $options->exchangeArray($arrayOptions);
	}

	public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		list($programmeOptions, $selectedProgramme, $gradeOptions, $selectedGrade, $academicPeriodOptions, $selectedAcademicPeriod) = array_values($this->_getSelectOptions());

		$this->field('education_programme_id', [
			'options' => $programmeOptions,
			'value' => $selectedProgramme
		]);
		$this->field('education_grade_id', [
			'options' => $gradeOptions,
			'value' => $selectedGrade
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
				$assessmentItems = $this->populateAssessmentItemsArray($entity, $selectedGrade);
				$entity->assessment_items = $this->AssessmentItems->newEntities($assessmentItems, ['validate'=>false]);
			} else {
				$entity->assessment_items = [];
			}
		}
	}

	public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra) {

	}

	public function addEditOnChangeProgramme(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		if (empty($requestData[$this->alias()]['education_programme_id'])) {
			$requestData[$this->alias()]['education_grade_id'] = '';
			$requestData[$this->alias()]['assessment_items'] = [];
		}
		$patchOptions['associated'] = [
			'AssessmentItems' => ['validate' => false],
			'AssessmentPeriods' => ['validate' => false]
		];
	}

	public function addEditOnChangeGrade(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		$extra['items_patched'] = true;
		$requestData[$this->alias()]['assessment_items'] = $this->populateAssessmentItemsArray($entity, $requestData[$this->alias()]['education_grade_id']);
		$patchOptions['associated'] = [
			'AssessmentItems' => ['validate' => false],
			'AssessmentPeriods' => ['validate' => false]
		];
	}

	public function addEditOnNewAssessmentPeriod(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		$extra['periods_patched'] = true;
		$requestData[$this->alias()]['assessment_periods'] = $this->populateAssessmentPeriodsArray($entity, $requestData);
		$patchOptions['associated'] = [
			'AssessmentItems' => ['validate' => false],
			'AssessmentPeriods' => ['validate' => false]
		];
	}


/******************************************************************************************************************
**
** specific field methods
**
******************************************************************************************************************/

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request) {
		$attr['onChangeReload'] = 'changeProgramme';
		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$attr['onChangeReload'] = 'changeGrade';
		return $attr;
	}

	public function onUpdateFieldAssessmentItems(Event $event, array $attr, $action, Request $request) {
		// $attr['type'] = 'customAssessmentItems';
		// $attr['element'] = 'Assessment.Assessments/subjects';
		// $attr['valueClass'] = 'table-full-width';
		
		return $attr;
	}

	public function onGetCustomAssessmentItemsElement(Event $event, $action, $entity, $attr, $options=[]) {
		$gradingTypeOptions = $this->AssessmentItems->GradingTypes->getList()->toArray();
		$tableHeaders = [__('Code'), __('Name'), __('Pass'), __('Max'), __('Grading Types'), __('Weight'), 'Delete'];
		// $tableHeaders = [__('Code'), __('Name'), __('Type'), __('Pass'), __('Max'), __('Grading Types'), 'Delete', ''];

		switch ($action) {
			case 'index':
				// no code required
				break;

			case 'view':
				$tableHeaders = [__('Code'), __('Name'), __('Pass'), __('Max'), __('Grading Types'), __('Weight')];
				foreach ($entity['assessment_items'] as $key => $value) {
					$rowData = [];

					$rowData[] = (array_key_exists('education_subject', $value) && !empty($value['education_subject']))? $value['education_subject']['code']: '';
					$rowData[] = (array_key_exists('education_subject', $value) && !empty($value['education_subject']))? $value['education_subject']['name']: '';
					$rowData[] = (array_key_exists('result_type', $value))? $value['result_type']: '';
					$rowData[] = (array_key_exists('pass_mark', $value))? $value['pass_mark']: '';
					$rowData[] = (array_key_exists('max', $value))? $value['max']: '';
					$gradingTypeId = (array_key_exists('assessment_grading_type_id', $value))? $value['assessment_grading_type_id']: '';
					$rowData[] = (array_key_exists($gradingTypeId, $gradingTypeOptions))? $gradingTypeOptions[$gradingTypeId]: '';

					$tableCells[] = $rowData;
				}
				break;

			case 'edit':
				$tableHeaders = [__('Code'), __('Name'), __('Pass'), __('Max'), __('Grading Types'), __('Weight'), 'Delete'];

				$EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
				$subjectData = $EducationGradesSubjects->find()
					->contain('EducationSubjects')
					->where([$EducationGradesSubjects->aliasField('education_grade_id') .' = '. $entity->education_grade_id]);

				$arraySubjects = [];
				$assessmentItems = [];
				foreach ($subjectData as $key => $value) {
					if (!empty($value->education_subject)) {
						// $educationSubjectOptions[$value->education_subject->id] = $value->education_subject->name;
						$arraySubjects[] = [
							'id' => $obj['id'],
							'education_subject_id' => $obj['education_subject']->id,
							'code' => $obj['education_subject']->code,
							'name' => $obj['education_subject']->name,
							'pass_mark' => $obj['pass_mark'],
							'max' => $obj['max'],
							'assessment_grading_type_id' => $obj['assessment_grading_type_id'],
							'education_grade_id' => $entity->education_grade_id,
							'weight' => '',
						];
					}
				}
				$cellCount = 0;

				// new inserted logic
				// if ($this->request->is(['get'])) {
				// 	$educationSubjects = $entity->assessment_items;
				// 	foreach ($educationSubjects as $key => $obj) {
				// 		if (array_key_exists('education_subject', $obj)) {
				// 			$arraySubjects[] = [
				// 				'id' => $obj['id'],
				// 				'education_subject_id' => $obj['education_subject']->id,
				// 				'name' => $obj['education_subject']->name,
				// 				'result_type' =>$obj['result_type'],
				// 				'code' => $obj['education_subject']->code,
				// 				'pass_mark' => $obj['pass_mark'],
				// 				'max' => $obj['max'],
				// 				'assessment_grading_type_id' => $obj['assessment_grading_type_id'],
				// 				'education_grade_id' => $entity->education_grade_id
				// 			];
				// 		}
						
				// 	}
				// } else if ($this->request->is(['post', 'put'])) {
				// 	$requestData = $this->request->data;
				// 	if (array_key_exists('assessment_items', $requestData[$this->alias()])) {
				// 		foreach ($requestData[$this->alias()]['assessment_items'] as $key => $obj) {
				// 			$arraySubjects[] = $obj;
				// 		}
				// 	}

				// 	if (array_key_exists('new_education_subject_id', $requestData[$this->alias()])) {
				// 		$subjectId = $requestData[$this->alias()]['new_education_subject_id'];

				// 		$subjectsToBeAdded = [];
				// 		if ($subjectId == 'ALL') {
				// 			$currSubjects = [];
				// 			foreach ($arraySubjects as $key => $value) {
				// 				$currSubjects[] = $value['education_subject_id'];
				// 			}
				// 			$subjectsToBeAdded = array_diff(array_keys($educationSubjectOptions), $currSubjects);
				// 		} else {
				// 			$subjectsToBeAdded[] = $subjectId;
				// 		}
						
				// 		foreach ($subjectsToBeAdded as $key => $value) {
				// 			$subjectObj = $this->EducationGrades->EducationSubjects
				// 				->findById($value)
				// 				->first();

				// 			$arraySubjects[] = [
				// 				'name' => $subjectObj->name,
				// 				'education_subject_id' => $subjectObj->id,
				// 				'result_type' => key($this->getSelectOptions($this->aliasField('mark_types'))),
				// 				'code' => $subjectObj->code,
				// 				'pass_mark' => 50,
				// 				'max' => 100,
				// 				'assessment_grading_type_id' => key($gradingTypeOptions),
				// 				'education_grade_id' => $entity->education_grade_id
				// 			];
				// 		}						
				// 	}
				// }

				foreach ($arraySubjects as $key => $value) {
					$fieldPrefix = $attr['model'] . '.assessment_items.' . $cellCount++;
					$assessmentItemId = array_key_exists('id', $value)? $value['id']: null;
					$subjectCode = $value['code'];
					$subjectName = $value['name'];

					$hiddenData = "";
					$form = $event->subject()->Form;
					if (!is_null($assessmentItemId)) $hiddenData .= $form->hidden($fieldPrefix.".id", ['value' => $assessmentItemId]);
					$hiddenData .= $form->hidden($fieldPrefix.".education_subject_id", ['value' => $value['education_subject_id']]);
					$hiddenData .= $form->hidden($fieldPrefix.".name", ['value' => $subjectName]);
					$hiddenData .= $form->hidden($fieldPrefix.".code", ['value' => $subjectCode]);
					$hiddenData .= $form->hidden($fieldPrefix.".education_grade_id", ['value' => $value['education_grade_id']]);

					if (isset($value['id'])) {
						$hiddenData .= $form->hidden($fieldPrefix.".id", ['value' => $value['id']]);
					}

					$rowData = [];
					$rowData[] = (array_key_exists('code', $value))? $value['code']: '';
					$rowData[] = (array_key_exists('name', $value))? $value['name']: '';

					// options
					$rowData[] = $form->input($fieldPrefix.".result_type", ['options' => $markTypeOptions, 'label' => false]);
					$rowData[] = $form->input($fieldPrefix.".pass_mark", ['label' => false, 'default' => 50]);
					$rowData[] = $form->input($fieldPrefix.".max", ['label' => false, 'default' => 100]);
					$rowData[] = $form->input($fieldPrefix.".assessment_grading_type_id", ['options' => $gradingTypeOptions, 'label' => false]);

					$resultCount = 0;
					if (!is_null($assessmentItemId)) {
						// need to find out if assessment_items are linked to any results
						$resultCount = $AssessmentItemResults->find()
							->where([$AssessmentItemResults->aliasField('assessment_item_id') .' = '. $assessmentItemId])
							->count();
					}
					if (!empty($resultCount)) {
						$rowData[] = $this->getInfoIcon(__('Associated results exist. Delete function disabled.'));
					} else {
						$rowData[] = $this->getDeleteButton();
					}

					$rowData[] = $hiddenData;
					$tableCells[] = $rowData;

					if (array_key_exists('education_subject_id', $value) && !empty($value['education_subject_id'])) {
						unset($educationSubjectOptions[$value['education_subject_id']]);
					}
				}

				ksort($educationSubjectOptions);
				$sortedEducationSubjectOptions = [];
				$sortedEducationSubjectOptions[0] = "-- ".__('Add Assessment Item') ." --";
				if (!empty($educationSubjectOptions)) $sortedEducationSubjectOptions['ALL'] = "-- ".__('Add All Assessment Items') ." --";
				$sortedEducationSubjectOptions = array_merge($sortedEducationSubjectOptions, $educationSubjectOptions);
				
				$attr['options'] = $sortedEducationSubjectOptions;
				break;
			
			default:
				// no code required
				break;
		}

		$attr['tableHeaders'] = (!empty($tableHeaders))? $tableHeaders: [];
		$attr['tableCells'] = (!empty($tableCells))? $tableCells: [];

		return $event->subject()->renderElement('Assessment.Assessments/subjects', ['attr' => $attr]);
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	public function populateAssessmentPeriodsArray(Entity $entity, ArrayObject $requestData) {
		$assessmentPeriods = [];
		if (array_key_exists($this->alias(), $requestData)) {
			if (array_key_exists('assessment_periods', $requestData[$this->alias()])) {
				$assessmentPeriods = $requestData[$this->alias()]['assessment_periods'];
			}
		}
		$assessmentPeriods[] = [
		    'id' => '',
			'code' => '',
		    'name' => '',
		    'start_date' => '',
		    'end_date' => '',
		    'date_enabled' => '',
		    'date_disabled' => '',
		    'weights' => '',
		    'assessment_id' => $entity->id,
		];
		return $assessmentPeriods;
	}

	public function populateAssessmentItemsArray(Entity $entity, $gradeId) {
		$EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
		$gradeSubjects = $EducationGradesSubjects->find()
			->contain('EducationSubjects')
			->where([$EducationGradesSubjects->aliasField('education_grade_id') => $gradeId])
			->toArray();

		$arraySubjects = [];
		foreach ($gradeSubjects as $key => $gradeSubject) {
			if (!empty($gradeSubject->education_subject)) {
				$arraySubjects[] = [
				    'id' => '',
				    'assessment_id' => $entity->id,
					'education_subject_id' => $gradeSubject->education_subject->id,
				    'assessment_grading_type_id' => '',
					'weights' => '',
				];
			}
		}
		return $arraySubjects;
	}

	public function _getSelectOptions() {
		$EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
		$EducationGrades = $this->EducationGrades;
		$AcademicPeriods = $this->AcademicPeriods;

		// Education Programmes
		$programmeOptions = $EducationProgrammes
			->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
			->find('visible')
			->contain(['EducationCycles'])
			->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
			->toArray();

		$selectedProgramme = $this->postString('education_programme_id');
		// $this->advancedSelectOptions($programmeOptions, $selectedProgramme, [
		// 	'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
		// 	'callable' => function($id) use ($EducationGrades) {
		// 		return $EducationGrades->findAllByEducationProgrammeId($id)->find('visible')->count();
		// 	}
		// ]);
		// $selectedProgramme = $this->queryString('programme', $programmeOptions);
		// $this->advancedSelectOptions($programmeOptions, $selectedProgramme, [
		// 	'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
		// 	'callable' => function($id) use ($EducationGrades) {
		// 		return $EducationGrades->findAllByEducationProgrammeId($id)->find('visible')->count();
		// 	}
		// ]);
		// End
		// pr($selectedProgramme);die;
		
		// Education Grades
		if (!empty($selectedProgramme)) {
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
		$academicPeriodOptions = $AcademicPeriods->getYearList();

		$selectedAcademicPeriod = $this->postString('academic_period_id');
		// End

		return compact('programmeOptions', 'selectedProgramme', 'gradeOptions', 'selectedGrade', 'academicPeriodOptions', 'selectedAcademicPeriod');
	}

}
