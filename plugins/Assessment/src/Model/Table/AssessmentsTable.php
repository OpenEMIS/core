<?php
namespace Assessment\Model\Table;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

class AssessmentsTable extends AppTable {
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		// $this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems']);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('type', ['visible' => false]);
		$this->ControllerAction->field('education_programmes');
		$this->ControllerAction->field('education_grade_id');
		$this->ControllerAction->field('education_subjects');

		
		$this->controller->set('selectedAction', $this->alias());

		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'description', 'visible', 'education_programmes', 
			'education_grade_id', 'education_subjects'
		]);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['visible', 'code', 'name']);
	}

	public function onGetEducationProgrammes(Event $event, Entity $entity) {
		return $entity->education_grade->programme_name;
	}

	public function onUpdateFieldEducationProgrammes(Event $event, array $attr, $action, Request $request) {
		$attr['visible'] = ['index' => false, 'view' => true, 'edit' => true];
		$EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
		$EducationGrades = $this->EducationGrades;

		$programmeOptions = $EducationProgrammes
			->find('list')
			->find('visible')
			->find('order')
			->toArray()
			;

		$selectedProgramme = $this->queryString('education_programmes', $programmeOptions);
		$this->advancedSelectOptions($programmeOptions, $selectedProgramme, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
			'callable' => function($id) use ($EducationGrades) {
				return $EducationGrades->findAllByEducationProgrammeId($id)->count();
			}
		]);

		$attr['options'] = $programmeOptions;
		$attr['onChangeReload'] = 'changeProgramme';

		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$programmeId = 0;
		if ($request->is('get')) {
			$programmeId = $request->query('education_programmes');
		} else {
			$programmeId = $request->data($this->aliasField('education_programmes'));
		}

		$gradeOptions = $this->EducationGrades
			->find('list')
			->find('visible')
			->find('order')
			->where([$this->EducationGrades->aliasField('education_programme_id') => $programmeId])
			->toArray();

		$attr['options'] = $gradeOptions;

		return $attr;
	}

	public function onUpdateFieldEducationSubjects(Event $event, array $attr, $action, Request $request) {
		if ($action != 'index') {
			$attr['type'] = 'element';
			$attr['element'] = 'Assessment.Assessments/subjects';

			if ($action == 'add') {
				if ($request->is('get')) {
					$educationGradeId = key($this->fields['education_grade_id']['options']);
					
					$result = $this->EducationGrades
						->findById($educationGradeId)
						->contain(['EducationSubjects'])
						->first();

					$attr['data'] = $result->education_subjects;
				} else {

				}
			}
		} else {
			$attr['visible'] = false;
		}
		return $attr;
	}

	// public function addEditOnChangeProgramme(Event $event, Entity $entity, array $data, array $options) {
	// 	$programmeId = $data[$this->alias()]['education_programmes'];
	// 	pr($programmeId);

		
	// }
}
