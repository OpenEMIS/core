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

class AssessmentsTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('AssessmentStatuses', ['className' => 'Assessment.AssessmentStatuses', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionAssessments', ['className' => 'Institution.InstitutionAssessments', 'dependent' => true]);

		$this->addBehavior('Reorder');
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
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$selectedProgramme = $this->EducationGrades->get($entity->education_grade_id)->education_programme_id;
		$entity->education_programmes = $selectedProgramme;
		$this->request->query['programme'] = $selectedProgramme;
		$this->request->query['grade'] = $entity->education_grade_id;
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (array_key_exists($this->alias(), $data)) {
			if (array_key_exists('assessment_items', $data[$this->alias()])) {
				foreach ($data[$this->alias()]['assessment_items'] as $i => $item) {
					if (strlen($item['pass_mark']) == 0) {
						$data[$this->alias()]['assessment_items'][$i]['pass_mark'] = 50;
					}
					if (strlen($item['max']) == 0) {
						$data[$this->alias()]['assessment_items'][$i]['max'] = 100;
					}
				}
			}
		}
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
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$selectedGrade = $entity->education_grade_id;
		$gradingTypeOptions = $this->ControllerAction->getVar('gradingTypeOptions');
		$entity->assessment_items = $this->populateAssessmentItems($entity, $selectedGrade, ['gradingTypeOptions' => $gradingTypeOptions]);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		if ($this->request->is(['post', 'put'])) {
			$selectedGrade = $entity->education_grade_id;
			if ($selectedGrade != $entity->getOriginal('education_grade_id')) {
				$gradingTypeOptions = $this->ControllerAction->getVar('gradingTypeOptions');
				$entity->assessment_items = $this->populateAssessmentItems($entity, $selectedGrade, ['gradingTypeOptions' => $gradingTypeOptions]);
			}
		}
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
		$attr['type'] = 'element';
		$attr['element'] = 'Assessment.Assessments/subjects';
		$attr['valueClass'] = 'table-full-width';

		return $attr;
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

		$obj = $this->EducationGrades
			->findById($gradeId)
			->contain(['EducationSubjects'])
			// ->contain([
			// 	'EducationGradesSubjects.EducationSubjects',
			// 	'EducationGradesSubjects' => function ($q) {
			// 		return $q->find('visible');
			// 	}
			// ])
			->first();

		$assessmentItems = [];
		foreach ($obj->education_subjects as $subject) {
			if ($subject->_joinData->visible == 1) {
				$item = [
					'id' => '',
					'visible' => 1,
					'education_subject_id' => $subject->id,
					'result_type' => key($this->getSelectOptions($this->aliasField('mark_types'))),
					'pass_mark' => 50,
					'max' => 100,
					'assessment_grading_type_id' => key($gradingTypeOptions),
					'education_subject' => $subject
				];
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
