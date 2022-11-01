<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionClassGradesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
                
                $this->addBehavior('Restful.RestfulAccessControl', [
                    'ScheduleTimetable' => ['index']
                ]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	public function getGradesByClass($classId) {
		$this->unbindModel(array('belongsTo' => array('EducationGrade')));
		$data = $this->find('all', array(
			'fields' => array('InstitutionClassGrade.id', 'EducationCycle.name', 'EducationProgramme.name', 'EducationGrade.name', 'EducationGrade.id'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionClassGrade.education_grade_id')
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				)
			),
			'conditions' => array(
				'InstitutionClassGrade.institution_class_id' => $classId,
				'InstitutionClassGrade.status' => 1
			),
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		$this->bindModel(array('belongsTo' => array('EducationGrade')));

		$list = array();
		foreach($data as $obj) {
			$id = $obj['EducationGrade']['id'];
			$cycleName = $obj['EducationCycle']['name'];
			$programmeName = $obj['EducationProgramme']['name'];
			$gradeName = $obj['EducationGrade']['name'];
			$list[$id] = sprintf('%s - %s - %s', $cycleName, $programmeName, $gradeName);
		}
		return $list;
	}
	
}
