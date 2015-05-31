<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteSectionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		// $this->belongsTo('Staff', ['className' => 'Staff.Staff', 'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionSiteShifts', ['className' => 'Institution.InstitutionSiteShifts']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
	}

	public function validationDefault(Validator $validator) {
		$validator->add('name', 'notBlank', [
			'rule' => 'notBlank'
		]);
		return $validator;
	}

	public function beforeAction() {
		if ($this->action == 'add') {
			$tabElements = [
				'single_grade' => [
					'url' => ['controller' => 'Institutions', 'action' => 'Sections', 'add'],
					'text' => __('Single Grade')
				],
				'MultiGradeSections' => [
					'url' => ['controller' => 'Institutions', 'action' => 'MultiGradeSections', 'add'],
					'text' => __('Multi Grades')
				]
			];
			
			$this->fields['academic_period_id']['type'] = 'select';
			$this->fields['academic_period_id']['attr'] = ['onchange' => "$('#reload').click()"];
			$this->fields['academic_period_id']['order'] = 0;

			$this->fields['name']['visible'] = false;
			$this->fields['staff_id']['visible'] = false;
			$this->fields['section_number']['visible'] = false;
			$this->fields['education_grade_id']['type'] = 'select';
			$this->fields['institution_site_shift_id']['type'] = 'select';

			$this->controller->set('tabElements', $tabElements);
			$this->controller->set('selectedAction', 'single_grade');
		}
	}

	public function afterAction() {
		if ($this->action == 'add') {
			
		}
	}
}
