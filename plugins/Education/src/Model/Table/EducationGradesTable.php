<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

class EducationGradesTable extends AppTable {
	private $_fieldOrder = ['name', 'code', 'education_programme_id', 'visible'];
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
		$this->hasMany('EducationGradesSubjects', ['className' => 'Education.EducationGradesSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
		// $this->belongsToMany('EducationSubjects', [
		// 	'className' => 'Education.EducationSubjects',
		// 	'joinTable' => 'education_grades_subjects',
		// 	'foreignKey' => 'education_grade_id',
		// 	'targetForeignKey' => 'education_subject_id'
		// ]);
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if (!$entity->isNew()) {
			$this->EducationGradesSubjects->updateAll(
				['visible' => 0],
				['education_grade_id' => $entity->id]
			);
		}
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('subjects', ['type' => 'custom_subject', 'valueClass' => 'table-full-width']);
		$this->_fieldOrder[] = 'subjects';
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Education.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);

		$this->_fieldOrder = ['visible', 'name', 'code', 'education_programme_id', 'subjects'];
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain([
			'EducationGradesSubjects.EducationSubjects',
			'EducationGradesSubjects' => function ($q) {
				return $q->find('visible');
			}
		]);
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('education_programme_id');
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		list($levelOptions, $selectedLevel, $programmeOptions, $selectedProgramme) = array_values($this->getSelectOptions());

        $this->controller->set(compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme'));

		$options['conditions'][] = [
        	$this->aliasField('education_programme_id') => $selectedProgramme
        ];
	}

	public function onGetCustomSubjectElement(Event $event, $action, $entity, $attr, $options=[]) {
		if ($action == 'index') {
			$value = $this->EducationGradesSubjects
				->findByEducationGradeId($entity->id)
				->find('visible')
				->count();
			$attr['value'] = $value;
		} else if ($action == 'view') {
			$tableHeaders = [__('Name'), __('Code'), __('Hours Required')];
			$tableCells = [];

			foreach ($entity->education_grades_subjects as $key => $obj) {
				$rowData = [];
				$rowData[] = $obj->education_subject->name;
				$rowData[] = $obj->education_subject->code;
				$rowData[] = $obj->hours_required;
				$tableCells[] = $rowData;
			}

			$attr['valueClass'] = 'table-full-width';
			$attr['tableHeaders'] = $tableHeaders;
	    	$attr['tableCells'] = $tableCells;
		} else if ($action == 'edit') {
			if (isset($entity->id)) {
				$form = $event->subject()->Form;
				// Build Education Subjects options
				$subjectOptions = $this->EducationGradesSubjects->EducationSubjects
					->find('list')
					->find('visible')
					->find('order')
					->toArray();
				// End

				$tableHeaders = [__('Name'), __('Code'), __('Hours Required'), ''];
				$tableCells = [];
				$cellCount = 0;

				foreach ($entity->education_grades_subjects as $key => $obj) {
					$fieldPrefix = $attr['model'] . '.education_grades_subjects.' . $cellCount++;
					$subjectId = $obj->education_subject_id;
					$subjectObj = $this->EducationGradesSubjects->EducationSubjects
						->findById($subjectId)
						->first();

					$cellData = "";
					$cellData .= $form->input($fieldPrefix.".hours_required", ['label' => false, 'type' => 'number', 'value' => $obj->hours_required]);
					$cellData .= $form->hidden($fieldPrefix.".name", ['value' => $subjectObj->name]);
					$cellData .= $form->hidden($fieldPrefix.".code", ['value' => $subjectObj->code]);
					$cellData .= $form->hidden($fieldPrefix.".education_grade_id", ['value' => $obj->education_grade_id]);
					$cellData .= $form->hidden($fieldPrefix.".education_subject_id", ['value' => $obj->education_subject_id]);
					$cellData .= $form->hidden($fieldPrefix.".visible", ['value' => 1]);
					if (isset($obj->id)) {
						$cellData .= $form->hidden($fieldPrefix.".id", ['value' => $obj->id]);
					}
					
					$rowData = [];
					$rowData[] = $subjectObj->name;
					$rowData[] = $subjectObj->code;
					$rowData[] = $cellData;
					$rowData[] = '<button onclick="jsTable.doRemove(this)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';

					$tableCells[] = $rowData;
					unset($subjectOptions[$subjectId]);
				}

				if (isset($entity->education_subject_id) && $entity->education_subject_id != 0) {
					$fieldPrefix = $attr['model'] . '.education_grades_subjects.' . $cellCount++;
					$subjectId = $entity->education_subject_id;
					$subjectObj = $this->EducationGradesSubjects->EducationSubjects
						->findById($subjectId)
						->first();
					
					$cellData = "";
					$cellData .= $form->input($fieldPrefix.".hours_required", ['label' => false, 'type' => 'number']);
					$cellData .= $form->hidden($fieldPrefix.".name", ['value' => $subjectObj->name]);
					$cellData .= $form->hidden($fieldPrefix.".code", ['value' => $subjectObj->code]);
					$cellData .= $form->hidden($fieldPrefix.".education_grade_id", ['value' => $entity->id]);
					$cellData .= $form->hidden($fieldPrefix.".education_subject_id", ['value' => $subjectObj->id]);
					$cellData .= $form->hidden($fieldPrefix.".visible", ['value' => 1]);

					$rowData = [];
					$rowData[] = $subjectObj->name;
					$rowData[] = $subjectObj->code;
					$rowData[] = $cellData;
					$rowData[] = '<button onclick="jsTable.doRemove(this)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';

					$tableCells[] = $rowData;
					unset($subjectOptions[$subjectId]);
				}

				$attr['valueClass'] = 'table-full-width';
				$attr['tableHeaders'] = $tableHeaders;
	    		$attr['tableCells'] = $tableCells;
	    		array_unshift($subjectOptions, "-- ".__('Add Subject') ." --");
	    		$attr['options'] = $subjectOptions;
		    }
		}

		return $event->subject()->renderElement('Education.subjects', ['attr' => $attr]);
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request) {
		list(, , $programmeOptions, $selectedProgramme) = array_values($this->getSelectOptions());
		$attr['options'] = $programmeOptions;
		if ($action == 'add') {
			$attr['default'] = $selectedProgramme;
		}

		return $attr;
	}

	public function onUpdateFieldSubjects(Event $event, array $attr, $action, Request $request) {
		return $attr;
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$levelOptions = $this->EducationProgrammes->EducationCycles->EducationLevels
			->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
			->find('visible')
			->find('order')
			->toArray();
		$selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

		$cycleIds = $this->EducationProgrammes->EducationCycles
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->find('visible')
			->where([$this->EducationProgrammes->EducationCycles->aliasField('education_level_id') => $selectedLevel])
			->toArray();

		$programmeOptions = $this->EducationProgrammes
			->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
			->find('visible')
			->find('order')
			->where([
				$this->EducationProgrammes->aliasField('education_cycle_id IN') => $cycleIds
			])
			->toArray();
		$selectedProgramme = !is_null($this->request->query('programme')) ? $this->request->query('programme') : key($programmeOptions);

		return compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme');
	}
}
