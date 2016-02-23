<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\HtmlTrait;

class AssessmentsTable extends AppTable {
	private $_contain = ['AssessmentItems'];

	use HtmlTrait;
	use OptionsTrait;
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('AssessmentStatuses', ['className' => 'Assessment.AssessmentStatuses', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionAssessments', ['className' => 'Institution.InstitutionAssessments', 'dependent' => true]);

		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'education_grade_id',
			]);
		}
	}

	public function beforeAction(Event $event) {
		$gradingTypeOptions = $this->AssessmentItems->GradingTypes->getList()->toArray();
		if (empty($gradingTypeOptions)) {
			$this->Alert->warning('Assessments.noGradingTypes');
		}
		$markTypeOptions = $this->getSelectOptions($this->aliasField('mark_types'));

		$this->controller->set('gradingTypeOptions', $gradingTypeOptions);
		$this->controller->set('markTypeOptions', $markTypeOptions);

		$this->ControllerAction->field('type', ['visible' => false]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain('AssessmentItems.EducationSubjects');
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('assessment_items');

		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'description', 'visible',
			'education_grade_id', 'assessment_items'
		]);

		$selectedGrade = $entity->education_grade_id;
		$gradingTypeOptions = $this->ControllerAction->getVar('gradingTypeOptions');
		$entity->assessment_items = $this->populateAssessmentItems($entity, $selectedGrade, ['gradingTypeOptions' => $gradingTypeOptions]);
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		// after add redirect to edit
		if ($entity->isNew()) {
			$url = $this->ControllerAction->url('edit');
			$url[1] = $entity->{$this->primaryKey()};
			$event->stopPropagation();
			return $this->controller->redirect($url);
			
		}
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$selectedProgramme = $this->EducationGrades->get($entity->education_grade_id)->education_programme_id;
		$entity->education_programmes = $selectedProgramme;
		$this->request->query['programme'] = $selectedProgramme;
		$this->request->query['grade'] = $entity->education_grade_id;
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (!array_key_exists('education_subjects', $data[$this->alias()])) {
			$data[$this->alias()]['education_subjects'] = [];
		}

		// Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = $this->_contain;

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		list($programmeOptions, $selectedProgramme, $gradeOptions, $selectedGrade) = array_values($this->_getSelectOptions());
		$entity->education_programmes = $selectedProgramme;
		$entity->education_grade_id = $selectedGrade;

		$this->ControllerAction->field('education_programmes', [
			'options' => $programmeOptions
		]);
		$this->ControllerAction->field('education_grade_id', [
			'options' => $gradeOptions
		]);
		$this->ControllerAction->field('assessment_items');

		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'description', 'visible',
			'education_programmes', 'education_grade_id', 'assessment_items'
		]);

		$gradingTypeOptions = $this->ControllerAction->getVar('gradingTypeOptions');
		$entity->assessment_items = $this->populateAssessmentItems($entity, $selectedGrade, ['gradingTypeOptions' => $gradingTypeOptions]);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->fields['education_programmes']['type'] = 'readonly';
		$this->fields['education_programmes']['attr']['value'] = $this->fields['education_programmes']['options'][$entity->education_programmes]['text'];
		$this->fields['education_grade_id']['type'] = 'readonly';
		$this->fields['education_grade_id']['attr']['value'] = $this->fields['education_grade_id']['options'][$entity->education_grade_id]['text'];

	}

	public function onUpdateFieldEducationProgrammes(Event $event, array $attr, $action, Request $request) {
		$attr['onChangeReload'] = 'changeProgramme';
		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$attr['onChangeReload'] = 'changeGrade';
		return $attr;
	}

	public function onUpdateFieldAssessmentItems(Event $event, array $attr, $action, Request $request) {
		$attr['type'] = 'customAssessmentItems';
		$attr['element'] = 'Assessment.Assessments/subjects';
		$attr['valueClass'] = 'table-full-width';
		
		return $attr;
	}

	public function onGetCustomAssessmentItemsElement(Event $event, $action, $entity, $attr, $options=[]) {
		$gradingTypeOptions = $this->AssessmentItems->GradingTypes->getList()->toArray();
		$tableHeaders = [__('Code'), __('Name'), __('Type'), __('Pass'), __('Max'), __('Grading Types'), 'Delete', ''];

		switch ($action) {
			case 'index':
				// no code required
				break;

			case 'view':
				$tableHeaders = [__('Code'), __('Name'), __('Type'), __('Pass'), __('Max'), __('Grading Types')];
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
				$tableHeaders = [__('Code'), __('Name'), __('Type'), __('Pass'), __('Max'), __('Grading Types'), 'Delete', ''];
				$markTypeOptions = $this->getSelectOptions($this->aliasField('mark_types'));

				$AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
				$EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
				$subjectData = $EducationGradesSubjects->find()
					->contain('EducationSubjects')
					->where([$EducationGradesSubjects->aliasField('education_grade_id') .' = '. $entity->education_grade_id]);

				$educationSubjectOptions = [];
				foreach ($subjectData as $key => $value) {
					if (!empty($value->education_subject)) {
						$educationSubjectOptions[$value->education_subject->id] = $value->education_subject->name;
					}
				}
				$cellCount = 0;

				// new inserted logic
				$arraySubjects = [];
				if ($this->request->is(['get'])) {
					$educationSubjects = $entity->assessment_items;
					foreach ($educationSubjects as $key => $obj) {
						if (array_key_exists('education_subject', $obj)) {
							$arraySubjects[] = [
								'id' => $obj['id'],
								'education_subject_id' => $obj['education_subject']->id,
								'name' => $obj['education_subject']->name,
								'result_type' =>$obj['result_type'],
								'code' => $obj['education_subject']->code,
								'pass_mark' => $obj['pass_mark'],
								'max' => $obj['max'],
								'assessment_grading_type_id' => $obj['assessment_grading_type_id'],
								'education_grade_id' => $entity->education_grade_id
							];
						}
						
					}
				} else if ($this->request->is(['post', 'put'])) {
					$requestData = $this->request->data;
					if (array_key_exists('assessment_items', $requestData[$this->alias()])) {
						foreach ($requestData[$this->alias()]['assessment_items'] as $key => $obj) {
							$arraySubjects[] = $obj;
						}
					}

					if (array_key_exists('new_education_subject_id', $requestData[$this->alias()])) {
						$subjectId = $requestData[$this->alias()]['new_education_subject_id'];

						$subjectsToBeAdded = [];
						if ($subjectId == 'ALL') {
							$currSubjects = [];
							foreach ($arraySubjects as $key => $value) {
								$currSubjects[] = $value['education_subject_id'];
							}
							$subjectsToBeAdded = array_diff(array_keys($educationSubjectOptions), $currSubjects);
						} else {
							$subjectsToBeAdded[] = $subjectId;
						}
						
						foreach ($subjectsToBeAdded as $key => $value) {
							$subjectObj = $this->EducationGrades->EducationSubjects
								->findById($value)
								->first();

							$arraySubjects[] = [
								'name' => $subjectObj->name,
								'education_subject_id' => $subjectObj->id,
								'result_type' => key($this->getSelectOptions($this->aliasField('mark_types'))),
								'code' => $subjectObj->code,
								'pass_mark' => 50,
								'max' => 100,
								'assessment_grading_type_id' => key($gradingTypeOptions),
								'education_grade_id' => $entity->education_grade_id
							];
						}						
					}
				}

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

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
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

	public function addEditOnChangeProgramme(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['programme']);
		unset($request->query['grade']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('education_programmes', $request->data[$this->alias()])) {
					$request->query['programme'] = $request->data[$this->alias()]['education_programmes'];
				}
			}
		}
	}

	public function addEditOnChangeGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['programme']);
		unset($request->query['grade']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('education_programmes', $request->data[$this->alias()])) {
					$request->query['programme'] = $request->data[$this->alias()]['education_programmes'];
				}
				if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
					$request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
				}
			}
		}
	}

	public function populateAssessmentItems($entity, $gradeId, $options=[]) {
		$gradingTypeOptions = $options['gradingTypeOptions'];

		$EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
		$query = $EducationGradesSubjects
			->find()
			->find('visible')
			->matching('EducationSubjects')
			->select([
				$this->AssessmentItems->aliasField('id'),
				$this->AssessmentItems->aliasField('pass_mark'),
				$this->AssessmentItems->aliasField('max'),
				$this->AssessmentItems->aliasField('result_type'),
				$this->AssessmentItems->aliasField('education_subject_id'),
				$this->AssessmentItems->aliasField('assessment_grading_type_id')
			])
			->leftJoin(
				[$this->AssessmentItems->alias() => $this->AssessmentItems->table()],
				[
					$this->AssessmentItems->aliasField('education_subject_id = ') . $EducationGradesSubjects->aliasField('education_subject_id'),
					$this->AssessmentItems->aliasField('assessment_id') => $entity->id
				]
			)
			->where([
				$EducationGradesSubjects->aliasField('education_grade_id') => $gradeId
			])
			->autoFields(true);

		$results = $query->toArray();

		$assessmentItems = [];
		foreach ($results as $obj) {
			if (isset($obj->AssessmentItems['id'])) {
				// Existing record
				$item = $obj->AssessmentItems;
				$item['education_subject'] = $obj->_matchingData['EducationSubjects'];
				$assessmentItems[] = $item;
			}
		}

		return $assessmentItems;
	}

	public function _getSelectOptions() {
		$EducationProgrammes = $this->EducationGrades->EducationProgrammes;
		$EducationGrades = $this->EducationGrades;

		// Education Programmes
		$programmeOptions = $EducationProgrammes
			->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
			->find('visible')
			->contain(['EducationCycles'])
			->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
			->toArray();

		$selectedProgramme = $this->queryString('programme', $programmeOptions);
		$this->advancedSelectOptions($programmeOptions, $selectedProgramme, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
			'callable' => function($id) use ($EducationGrades) {
				return $EducationGrades->findAllByEducationProgrammeId($id)->find('visible')->count();
			}
		]);
		// End

		// Education Grades
		$gradeOptions = $EducationGrades
			// ->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
			->find('list')
			->find('visible')
			->contain(['EducationProgrammes'])
			->where([$EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
			->order(['EducationProgrammes.order' => 'ASC', $EducationGrades->aliasField('order') => 'ASC'])
			->toArray();

		$selectedGrade = $this->queryString('grade', $gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $selectedGrade);
		// End

		return compact('programmeOptions', 'selectedProgramme', 'gradeOptions', 'selectedGrade');
	}
}
