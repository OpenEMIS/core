<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_sites');
		parent::initialize($config);

		/**
		 * fieldOption tables
		 */
		$this->belongsTo('InstitutionSiteLocalities', 		['className' => 'Institution.Localities']);
		$this->belongsTo('InstitutionSiteTypes', 			['className' => 'Institution.Types']);
		$this->belongsTo('InstitutionSiteOwnerships', 		['className' => 'Institution.Ownerships']);
		$this->belongsTo('InstitutionSiteStatuses', 		['className' => 'Institution.Statuses']);
		$this->belongsTo('InstitutionSiteSectors', 			['className' => 'Institution.Sectors']);
		$this->belongsTo('InstitutionSiteProviders', 		['className' => 'Institution.Providers']);
		$this->belongsTo('InstitutionSiteGenders', 			['className' => 'Institution.Genders']);

		$this->belongsTo('Areas', 							['className' => 'Area.Areas']);
		$this->belongsTo('AreaAdministratives', 			['className' => 'Area.AreaAdministratives']);

		// $this->hasMany('InstitutionSiteActivities', 		['className' => 'Institution.InstitutionSiteActivities', 'dependent' => true]);
		// $this->hasMany('InstitutionSiteAttachments', 		['className' => 'Institution.InstitutionSiteAttachments', 'dependent' => true]);

		// $this->hasMany('InstitutionSitePositions', 			['className' => 'Institution.InstitutionSitePositions', 'dependent' => true]);
		// $this->hasMany('InstitutionSiteProgrammes', 		['className' => 'Institution.InstitutionSiteProgrammes', 'dependent' => true]);
		// $this->hasMany('InstitutionSiteShifts', 			['className' => 'Institution.InstitutionSiteShifts', 'dependent' => true]);
		// $this->hasMany('InstitutionSiteSections', 			['className' => 'Institution.InstitutionSiteSections', 'dependent' => true, 'cascadeCallbacks' => true]);
		// $this->hasMany('InstitutionSiteClasses', 			['className' => 'Institution.InstitutionSiteClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
		// $this->hasMany('Infrastructures',					['className' => 'Institution.InstitutionInfrastructures', 'dependent' => true, 'cascadeCallbacks' => true]);

		// $this->hasMany('InstitutionSiteStaff', 				['className' => 'Institution.InstitutionSiteStaff', 'dependent' => true]);
		// $this->hasMany('StaffBehaviours', 					['className' => 'Institution.StaffBehaviours', 'dependent' => true]);
		// $this->hasMany('InstitutionSiteStaffAbsences', 		['className' => 'Institution.InstitutionSiteStaffAbsences', 'dependent' => true]);

		// $this->hasMany('InstitutionSiteStudents', 			['className' => 'Institution.InstitutionSiteStudents', 'dependent' => true]);
		// $this->hasMany('StudentBehaviours', 				['className' => 'Institution.StudentBehaviours', 'dependent' => true]);
		// $this->hasMany('InstitutionSiteStudentAbsences', 	['className' => 'Institution.InstitutionSiteStudentAbsences', 'dependent' => true]);

		// $this->hasMany('InstitutionSiteBankAccounts', 		['className' => 'Institution.InstitutionSiteBankAccounts', 'dependent' => true]);
		// $this->hasMany('InstitutionSiteFees', 				['className' => 'Institution.InstitutionSiteFees', 'dependent' => true]);

		// $this->hasMany('InstitutionSiteGrades', 			['className' => 'Institution.InstitutionSiteGrades', 'dependent' => true]);
		
		// $this->hasMany('InstitutionGradeStudents', 			['className' => 'Institution.InstitutionGradeStudents', 'foreignKey' => 'institution_id', 'dependent' => true]);
		
		$this->addBehavior('Excel', ['excludes' => ['security_group_id']]);
		$this->addBehavior('Report.ReportList');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.Report.onChangeFeature'] = 'onChangeFeature';
		$events['Model.Report.onGetName'] = 'onGetReportName';
		return $events;
	}

	public function beforeAction(Event $event) {
		if ($this->action == 'export') {
			$this->fields = [];

			$this->ControllerAction->field('feature');
			$this->ControllerAction->field('format');
		}
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = [
			$this->registryAlias() => __('Overview'),
			'Report.InstitutionPositions' => __('Positions'),
			'Report.InstitutionProgrammes' => __('Programmes'),
		];

		$attr['onChangeReload'] = 'changeFeature';
		return $attr;
	}

	// public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request) {
	// 	$attr['options'] = [
	// 		$this->alias() => __('Overview'),
	// 		'InstitutionPositions' => __('Positions'),
	// 		'InstitutionProgrammes' => __('Programmes'),
	// 	];

	// 	$attr['onChangeReload'] = 'changeFeature';
	// 	return $attr;
	// }

	public function onChangeFeature(Event $event, Entity $entity, ArrayObject $data) {
		$feature = $this->request->data($this->aliasField('feature'));
	}

	public function onGetReportName(Event $event, ArrayObject $data) {
		return __('Overview');
	}

	public function onExcelGenerate(Event $event, $writer, $settings) {
		// pr($settings);
		// $generate = function() { pr('dsa'); };
		// return $generate;
	}

	public function onExcelBeforeQuery(Event $event, Query $query) {
		// pr($this->Session->read($this->aliasField('id')));die;
		// $query->where(['Institutions.id' => 2]);
	}

	// public function onExcelGetLabel(Event $event, $column) {
	// 	return 'asd';
	// }

	// public function 
}
