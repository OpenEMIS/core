<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

class EducationGradesTable extends AppTable {
	private $_contain = ['EducationSubjects._joinData'];
	private $_fieldOrder = ['name', 'code', 'education_programme_id', 'visible'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
		$this->hasMany('Programmes', ['className' => 'Institution.InstitutionGrades', 'cascadeCallbacks' => true, 'dependent' => true]);
		$this->hasMany('Assessments', ['className' => 'Assessment.Assessments', 'cascadeCallbacks' => true, 'dependent' => true]);
		$this->hasMany('InstitutionSiteFees', ['className' => 'Institution.InstitutionSiteFees', 'cascadeCallbacks' => true, 'dependent' => true]);
		$this->hasMany('Rubrics', ['className' => 'Institution.InstitutionRubrics', 'cascadeCallbacks' => true, 'dependent' => true]);
		$this->hasMany('Visits', ['className' => 'Institution.InstitutionQualityVisits', 'cascadeCallbacks' => true, 'dependent' => true]);
		$this->hasMany('InstitutionSiteSectionGrades', ['className' => 'Institution.InstitutionSiteSectionGrades', 'cascadeCallbacks' => true, 'dependent' => true]);
		$this->hasMany('InstitutionSiteSectionStudents', ['className' => 'Institution.InstitutionSiteSectionStudents', 'cascadeCallbacks' => true, 'dependent' => true]);
		$this->hasMany('InstitutionStudents', ['className' => 'Institution.Students', 'cascadeCallbacks' => true, 'dependent' => true]);
		$this->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission', 'cascadeCallbacks' => true, 'dependent' => true]);
		$this->hasMany('StudentDropout', ['className' => 'Institution.StudentDropout', 'cascadeCallbacks' => true, 'dependent' => true]);

		$this->belongsToMany('EducationSubjects', [
			'className' => 'Education.EducationSubjects',
			'joinTable' => 'education_grades_subjects',
			'foreignKey' => 'education_grade_id',
			'targetForeignKey' => 'education_subject_id',
			'through' => 'Education.EducationGradesSubjects',
			'dependent' => true,
			// 'saveStrategy' => 'append'
		]);

		$this->addBehavior('ControllerAction.Delete');
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if (!$entity->isNew()) {
			if ($entity->setVisible) {
				// to be revisit
				// $EducationGradesSubjects = TableRegistry::get('EducationGradesSubjects');
				// $EducationGradesSubjects->updateAll(
				// 	['visible' => 0],
				// 	['education_grade_id' => $entity->id]
				// );
			}
		}
	}

	 /**
     * Method to get the education system id for the particular grade given
     *
     * @param integer $gradeId The grade id to check for
     * @return integer Education system id that the grade belongs to
     */
	public function getEducationSystemId($gradeId) {
		$educationSystemId = $this->find()
			->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
			->where([$this->aliasField('id') => $gradeId])
			->first();
		return $educationSystemId->education_programme->education_cycle->education_level->education_system->id;
	}

	 /**
     * Method to check the list of the grades that belongs to the education system
     *
     * @param integer $systemId The education system id to check for
     * @return array A list of the education system grades belonging to that particular education system
     */
	public function getEducationGradesBySystem($systemId) {
		$educationSystemId = $this->find('list', [
				'keyField' => 'id',
				'valueField' => 'id'
			])
			->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
			->where(['EducationSystems.id' => $systemId])->toArray();
		return $educationSystemId;
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
		$query->where([$this->aliasField('education_programme_id') => $entity->education_programme_id]);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		if ($this->associationCount($this, $id) > 0) {
			$this->Alert->error('EducationGrades.hasAssociation');
			$event->stopPropagation();
			return $this->controller->redirect($this->ControllerAction->url('index'));
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

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list($levelOptions, $selectedLevel, $programmeOptions, $selectedProgramme) = array_values($this->_getSelectOptions());
        $this->controller->set(compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme'));

		$query->where([$this->aliasField('education_programme_id') => $selectedProgramme]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['EducationSubjects']);
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('education_programme_id');
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// to be revisit
		// $data[$this->alias()]['setVisible'] = true;

		// To handle when delete all subjects
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

	public function onGetCustomSubjectElement(Event $event, $action, $entity, $attr, $options=[]) {
		if ($action == 'index') {
			$EducationGradesSubjects = TableRegistry::get('EducationGradesSubjects');
			$value = $EducationGradesSubjects
				->findByEducationGradeId($entity->id)
				->where([$EducationGradesSubjects->aliasField('visible') => 1])
				->count();
			$attr['value'] = $value;
		} else if ($action == 'view') {
			$tableHeaders = [__('Name'), __('Code'), __('Hours Required')];
			$tableCells = [];

			$educationSubjects = $entity->extractOriginal(['education_subjects']);
			foreach ($educationSubjects['education_subjects'] as $key => $obj) {
				if ($obj->_joinData->visible == 1) {
					$rowData = [];
					$rowData[] = $obj->name;
					$rowData[] = $obj->code;
					$rowData[] = $obj->_joinData->hours_required;
					$tableCells[] = $rowData;
				}
			}

			$attr['tableHeaders'] = $tableHeaders;
	    	$attr['tableCells'] = $tableCells;
		} else if ($action == 'edit') {
			if (isset($entity->id)) {
				$form = $event->subject()->Form;
				// Build Education Subjects options
				$subjectOptions = $this->EducationSubjects
					->find('list')
					->find('visible')
					->find('order')
					->toArray();
				// End

				$tableHeaders = [__('Name'), __('Code'), __('Hours Required'), ''];
				$tableCells = [];
				$cellCount = 0;

				$arraySubjects = [];
				if ($this->request->is(['get'])) {
					$educationSubjects = $entity->extractOriginal(['education_subjects']);
					foreach ($educationSubjects['education_subjects'] as $key => $obj) {
						if ($obj->_joinData->visible == 1) {
							$arraySubjects[] = [
								'id' => $obj->_joinData->id,
								'name' => $obj->name,
								'code' => $obj->code,
								'hours_required' => $obj->_joinData->hours_required,
								'education_grade_id' => $obj->_joinData->education_grade_id,
								'education_subject_id' => $obj->_joinData->education_subject_id,
								'visible' => $obj->_joinData->visible
							];
						}
					}
				} else if ($this->request->is(['post', 'put'])) {
					$requestData = $this->request->data;
					if (array_key_exists('education_subjects', $requestData[$this->alias()])) {
						foreach ($requestData[$this->alias()]['education_subjects'] as $key => $obj) {
							$arraySubjects[] = $obj['_joinData'];
						}
					}

					if (array_key_exists('education_subject_id', $requestData[$this->alias()])) {
						$subjectId = $requestData[$this->alias()]['education_subject_id'];
						$subjectObj = $this->EducationSubjects
							->findById($subjectId)
							->first();

						$arraySubjects[] = [
							'name' => $subjectObj->name,
							'code' => $subjectObj->code,
							'hours_required' => 0,
							'education_grade_id' => $entity->id,
							'education_subject_id' => $subjectObj->id,
							'visible' => 1
						];
					}
				}

				foreach ($arraySubjects as $key => $obj) {
					$fieldPrefix = $attr['model'] . '.education_subjects.' . $cellCount++;
					$joinDataPrefix = $fieldPrefix . '._joinData';

					$subjectId = $obj['education_subject_id'];
					$subjectCode = $obj['code'];
					$subjectName = $obj['name'];

					$cellData = "";
					$cellData .= $form->input($joinDataPrefix.".hours_required", ['label' => false, 'type' => 'number', 'value' => $obj['hours_required']]);
					$cellData .= $form->hidden($fieldPrefix.".id", ['value' => $subjectId]);
					$cellData .= $form->hidden($joinDataPrefix.".name", ['value' => $subjectName]);
					$cellData .= $form->hidden($joinDataPrefix.".code", ['value' => $subjectCode]);
					$cellData .= $form->hidden($joinDataPrefix.".education_grade_id", ['value' => $obj['education_grade_id']]);
					$cellData .= $form->hidden($joinDataPrefix.".education_subject_id", ['value' => $subjectId]);
					$cellData .= $form->hidden($joinDataPrefix.".visible", ['value' => $obj['visible']]);
					if (isset($obj['id'])) {
						$cellData .= $form->hidden($joinDataPrefix.".id", ['value' => $obj['id']]);
					}

					$rowData = [];
					$rowData[] = $subjectName;
					$rowData[] = $subjectCode;
					$rowData[] = $cellData;
					$rowData[] = '<button onclick="jsTable.doRemove(this)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';

					$tableCells[] = $rowData;
					unset($subjectOptions[$obj['education_subject_id']]);
				}

				$attr['tableHeaders'] = $tableHeaders;
	    		$attr['tableCells'] = $tableCells;

	    		$subjectOptions[0] = "-- ".__('Add Subject') ." --";
	    		ksort($subjectOptions);
	    		$attr['options'] = $subjectOptions;
			}
		}

		return $event->subject()->renderElement('Education.subjects', ['attr' => $attr]);
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request) {
		list(, , $programmeOptions, $selectedProgramme) = array_values($this->_getSelectOptions());
		$attr['options'] = $programmeOptions;
		if ($action == 'add') {
			$attr['default'] = $selectedProgramme;
		}

		return $attr;
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$levelOptions = $this->EducationProgrammes->EducationCycles->EducationLevels->getLevelOptions();
		$selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

		$cycleIds = $this->EducationProgrammes->EducationCycles
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->find('visible')
			->where([$this->EducationProgrammes->EducationCycles->aliasField('education_level_id') => $selectedLevel])
			->toArray();

		$EducationProgrammes = $this->EducationProgrammes;
		$programmeOptions = $EducationProgrammes
			->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
			->find('visible')
			->contain(['EducationCycles'])
			->order([
				$EducationProgrammes->EducationCycles->aliasField('order'),
				$EducationProgrammes->aliasField('order')
			])
			->where([
				$EducationProgrammes->aliasField('education_cycle_id IN') => $cycleIds
			])
			->toArray();
		$selectedProgramme = !is_null($this->request->query('programme')) ? $this->request->query('programme') : key($programmeOptions);

		return compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme');
	}
}
