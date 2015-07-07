<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;

class InstitutionRubricsTable extends AppTable {
	private $status = [
		0 => 'New',
		1 => 'Draft',
		2 => 'Completed',
	];

	public function initialize(array $config) {
		$this->table('institution_site_quality_rubrics');
		parent::initialize($config);

		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('Sections', ['className' => 'Institution.InstitutionSiteSections', 'foreignKey' => 'institution_site_section_id']);
		$this->belongsTo('Classes', ['className' => 'Institution.InstitutionSiteClasses', 'foreignKey' => 'institution_site_class_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function indexBeforeAction(Event $event) {
		list($statusOptions, $selectedStatus) = array_values($this->getSelectOptions());

		$tabElements = [
			'New' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Rubrics?status=0'],
				'text' => __('New')
			],
			'Draft' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Rubrics?status=1'],
				'text' => __('Draft')
			],
			'Completed' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Rubrics?status=2'],
				'text' => __('Completed')
			]
		];

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $statusOptions[$selectedStatus]);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		list(, $selectedStatus) = array_values($this->getSelectOptions());
		$options['conditions'][$this->aliasField('status')] = $selectedStatus;
		$options['order'] = [
			$this->AcademicPeriods->aliasField('order')
		];
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$statusOptions = $this->status;
		$selectedStatus = !is_null($this->request->query('status')) ? $this->request->query('status') : key($statusOptions);

		return compact('statusOptions', 'selectedStatus');
	}
}
