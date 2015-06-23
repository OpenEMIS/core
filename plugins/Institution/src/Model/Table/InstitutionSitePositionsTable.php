<?php
namespace Institution\Model\Table;

use DateTime;
use DateInterval;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;

class InstitutionSitePositionsTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
		$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);

	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	public function beforeAction($event) {

		$this->fields['staff_position_title_id']['type'] = 'select';
		$this->fields['staff_position_grade_id']['type'] = 'select';

		$order = $this->fields['staff_position_grade_id']['order'] + 1;
		$this->fields['type']['order'] = $order;
		$this->fields['type']['type'] = 'select';
		$this->fields['type']['options'] = $this->getSelectOptions('Staff.position_types');
		$this->fields['status']['order'] = $order + 1;
		$this->fields['status']['type'] = 'select';
		$this->fields['status']['options'] = $this->getSelectOptions('general.active');

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}

	public function viewBeforeQuery($event) {
		// pr('viewBeforeQuery');
		// pr($this->id);
		// pr($event->action);
		// pr($this->controller->request->params);
		// return true;
	}

	public function viewBeforeAction($event) {
		$viewVars = $this->ControllerAction->vars();
		$id = $viewVars['_buttons']['view']['url'][1];

		$session = $this->controller->request->session();

		// start Current Staff List field
		$this->ControllerAction->addField('current_staff_list', [
			'type' => 'element', 
			'order' => 10,
			'element' => 'Institution.Positions/current'
		]);

		$Staff = $this->Institutions->InstitutionSiteStaff;
		$currentStaff = $Staff ->find('all')
							->where([$Staff->aliasField('end_date').' IS NULL'])
							->order([$Staff->aliasField('start_date')])
							->find('withBelongsTo')
							->find('byPosition', ['InstitutionSitePositions.id'=>$id])
							->find('byInstitution', ['Institutions.id'=>$session->read('Institutions.id')])
							;

		$this->fields['current_staff_list']['data'] = $currentStaff;
		$totalCurrentFTE = '0.00';
		if (count($currentStaff)>0) {
			foreach ($currentStaff as $cs) {
				$totalCurrentFTE = number_format((floatVal($totalCurrentFTE) + floatVal($cs->FTE)),2);
			}
		}
		$this->fields['current_staff_list']['totalCurrentFTE'] = $totalCurrentFTE;
		// end Current Staff List field

		// start Current Staff List field
		$this->ControllerAction->addField('past_staff_list', [
			'type' => 'element', 
			'order' => 11,
			'element' => 'Institution.Positions/past'
		]);

		$pastStaff = $Staff ->find('all')
							->where([$Staff->aliasField('end_date').' IS NOT NULL'])
							->order([$Staff->aliasField('start_date')])
							->find('withBelongsTo')
							->find('byPosition', ['InstitutionSitePositions.id'=>$id])
							->find('byInstitution', ['Institutions.id'=>$session->read('Institutions.id')])
							;

		$this->fields['past_staff_list']['data'] = $pastStaff;
		// end Current Staff List field

		return true;
	}

	public function addBeforeAction($event) {
	}

}
