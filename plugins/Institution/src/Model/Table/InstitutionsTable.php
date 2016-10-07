<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\I18n\I18n;
use Cake\ORM\ResultSet;
use Cake\Network\Session;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionsTable extends AppTable  {
	use OptionsTrait;
	private $dashboardQuery = null;

	public $shiftTypes = [];

	private $isAcademicOptions = [];

	CONST SINGLE_OWNER = 1;
	CONST SINGLE_OCCUPIER = 2;
	CONST MULTIPLE_OWNER = 3;
	CONST MULTIPLE_OCCUPIER = 4;

	// For Academic / Non-Academic Institution type
	const ACADEMIC = 1;
	const NON_ACADEMIC = 0;

	public function initialize(array $config) {
		$this->table('institutions');
        parent::initialize($config);

		/**
		 * fieldOption tables
		 */
		$this->belongsTo('Localities', 						['className' => 'Institution.Localities', 'foreignKey' => 'institution_locality_id']);
		$this->belongsTo('Types', 							['className' => 'Institution.Types', 'foreignKey' => 'institution_type_id']);
		$this->belongsTo('Ownerships',				 		['className' => 'Institution.Ownerships', 'foreignKey' => 'institution_ownership_id']);
		$this->belongsTo('Statuses', 						['className' => 'Institution.Statuses', 'foreignKey' => 'institution_status_id']);
		$this->belongsTo('Sectors',				 			['className' => 'Institution.Sectors', 'foreignKey' => 'institution_sector_id']);
		$this->belongsTo('Providers',				 		['className' => 'Institution.Providers', 'foreignKey' => 'institution_provider_id']);
		$this->belongsTo('Genders',				 			['className' => 'Institution.Genders', 'foreignKey' => 'institution_gender_id']);
		$this->belongsTo('NetworkConnectivities', 			['className' => 'Institution.NetworkConnectivities', 'foreignKey' => 'institution_network_connectivity_id']);
		/**
		 * end fieldOption tables
		 */

		$this->belongsTo('Areas', 							['className' => 'Area.Areas']);
		$this->belongsTo('AreaAdministratives', 			['className' => 'Area.AreaAdministratives']);

		$this->hasMany('InstitutionActivities', 			['className' => 'Institution.InstitutionActivities', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionAttachments', 			['className' => 'Institution.InstitutionAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->hasMany('InstitutionPositions', 				['className' => 'Institution.InstitutionPositions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionShifts', 				['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'location_institution_id']);
		$this->hasMany('InstitutionClasses', 				['className' => 'Institution.InstitutionClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
        // Note: InstitutionClasses already cascade deletes 'InstitutionSubjectStudents' - dependent and cascade not neccessary
        $this->hasMany('InstitutionSubjectStudents',        ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSubjects', 				['className' => 'Institution.InstitutionSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('Infrastructures',					['className' => 'Institution.InstitutionInfrastructures', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->hasMany('Staff',				 				['className' => 'Institution.Staff', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StaffPositionProfiles',				['className' => 'Institution.StaffPositionProfiles', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StaffBehaviours', 					['className' => 'Institution.StaffBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionStaffAbsences', 			['className' => 'Institution.StaffAbsences', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->hasMany('Students', 							['className' => 'Institution.Students', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StudentBehaviours', 				['className' => 'Institution.StudentBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionStudentAbsences', 		['className' => 'Institution.InstitutionStudentAbsences', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->hasMany('InstitutionBankAccounts', 			['className' => 'Institution.InstitutionBankAccounts', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionFees', 					['className' => 'Institution.InstitutionFees', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->hasMany('InstitutionGrades', 				['className' => 'Institution.InstitutionGrades', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->hasMany('StudentPromotion', 					['className' => 'Institution.StudentPromotion', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StudentAdmission', 					['className' => 'Institution.StudentAdmission', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StudentDropout', 					['className' => 'Institution.StudentDropout', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('TransferApprovals', 				['className' => 'Institution.TransferApprovals', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'previous_institution_id']);
		$this->hasMany('AssessmentItemResults', 			['className' => 'Institution.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionRubrics', 				['className' => 'Institution.InstitutionRubrics', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionQualityVisits', 			['className' => 'Institution.InstitutionQualityVisits', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StudentSurveys', 					['className' => 'Student.StudentSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSurveys', 				['className' => 'Institution.InstitutionSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->hasMany('ExaminationCentres',				['className' => 'Examination.ExaminationCentres', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->belongsToMany('SecurityGroups', [
			'className' => 'Security.SystemGroups',
			'joinTable' => 'security_group_institutions',
			'foreignKey' => 'institution_id',
			'targetForeignKey' => 'security_group_id',
			'through' => 'Security.SecurityGroupInstitutions',
			'dependent' => true
		]);

		$this->addBehavior('CustomField.Record', [
			'fieldKey' => 'institution_custom_field_id',
			'tableColumnKey' => 'institution_custom_table_column_id',
			'tableRowKey' => 'institution_custom_table_row_id',
			'fieldClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFields'],
			'formKey' => 'institution_custom_form_id',
			'filterKey' => 'institution_custom_filter_id',
			'formFieldClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFields'],
			'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
			'recordKey' => 'institution_id',
			'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
		$this->addBehavior('Year', ['date_opened' => 'year_opened', 'date_closed' => 'year_closed']);
        $this->addBehavior('TrackActivity', ['target' => 'Institution.InstitutionActivities', 'key' => 'institution_id', 'session' => 'Institution.Institutions.id']);
        $this->addBehavior('AdvanceSearch', [
        	'display_country' => false
        ]);
        $this->addBehavior('Excel', ['excludes' => ['security_group_id'], 'pages' => ['view']]);
        $this->addBehavior('Security.Institution');
        $this->addBehavior('Area.Areapicker');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('OpenEmis.Map');
        $this->addBehavior('HighChart', ['institutions' => ['_function' => 'getNumberOfInstitutionsByModel']]);
        $this->addBehavior('Import.ImportLink');

        $this->shiftTypes = $this->getSelectOptions('Shifts.types'); //get from options trait

        $this->isAcademicOptions = [
			self::ACADEMIC => 'Academic Institution',
			self::NON_ACADEMIC => 'Non-Academic Institution'
		];
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		$session = new Session();
		$userId = $session->read('Auth.User.id');
		$superAdmin = $session->read('Auth.User.super_admin');

		$validator
			->add('date_opened', [
					'ruleCompare' => [
						'rule' => ['comparison', 'notequal', '0000-00-00'],
					]
				])

	        ->allowEmpty('date_closed')
 	        ->add('date_closed', 'ruleCompareDateReverse', [
		            'rule' => ['compareDateReverse', 'date_opened', false]
	    	    ])

	        ->allowEmpty('longitude')
			->add('longitude', 'ruleLongitude', [
					'rule' => 'checkLongitude'
				])

	        ->allowEmpty('latitude')
			->add('latitude', 'ruleLatitude', [
					'rule' => 'checkLatitude'
				])

			// ->add('address', 'ruleMaximum255', [
			// 		'rule' => ['maxLength', 255],
			// 		'message' => 'Maximum allowable character is 255',
			// 		'last' => true
			// 	])

			->add('code', 'ruleUnique', [
	        		'rule' => 'validateUnique',
	        		'provider' => 'table',
	        		// 'message' => 'Code has to be unique'
			    ])

	        ->allowEmpty('email')
			->add('email', [
					'ruleValidEmail' => [
						'rule' => 'email'
					]
				])
			->add('area_id', 'ruleAuthorisedArea', [
					'rule' => ['checkAuthorisedArea', $superAdmin, $userId]
				])
			->add('institution_provider_id', 'ruleLinkedSector', [
						'rule' => 'checkLinkedSector',
						'provider' => 'table'
				])
	        ;
		return $validator;
	}

	public function getNonAcademicConstant() {
		return self::NON_ACADEMIC;
	}

	public function getAcademicConstant() {
		return self::ACADEMIC;
	}

	public function implementedEvents()
	{
		$events = parent::implementedEvents();
		$events['AdvanceSearch.getCustomFilter'] = 'getCustomFilter';
		return $events;
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
		$cloneFields = $fields->getArrayCopy();
		$newFields = [];
		foreach ($cloneFields as $key => $value) {
			$newFields[] = $value;
			if ($value['field'] == 'area_id') {
				$newFields[] = [
					'key' => 'Areas.code',
					'field' => 'area_code',
					'type' => 'string',
					'label' => ''
				];
			}
		}
		$fields->exchangeArray($newFields);
	}

	public function onExcelGetShiftType(Event $event, Entity $entity) {
		if (isset($this->shiftTypes[$entity->shift_type])) {
			return __($this->shiftTypes[$entity->shift_type]);
		} else {
			return '';
		}
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$query
			->contain(['Areas'])
			->select(['area_code' => 'Areas.code']);
	}

	public function onGetName(Event $event, Entity $entity) {
		$name = $entity->name;

		if ($this->AccessControl->check([$this->controller->name, 'dashboard'])) {
			$name = $event->subject()->HtmlField->link($entity->name, [
				'plugin' => $this->controller->plugin,
				'controller' => $this->controller->name,
				'action' => 'dashboard',
				'0' => $entity->id
			]);
		}

		return $name;
	}

	public function onGetShiftType(Event $event, Entity $entity)
	{
		$type = '-';
		if (array_key_exists($entity->shift_type, $this->shiftTypes)) {
			$type = $this->shiftTypes[$entity->shift_type];
		}
		return $type;
	}

	public function getViewShiftDetail($institutionId, $academicPeriod)
	{
		$data = $this->InstitutionShifts->find()
				->innerJoinWith('Institutions')
				->innerJoinWith('LocationInstitutions')
				->innerJoinWith('ShiftOptions')
				->select([
					'Owner' => 'Institutions.name',
					'OwnerId' => 'Institutions.id',
					'Occupier' => 'LocationInstitutions.name',
					'OccupierId' => 'LocationInstitutions.id',
					'Shift' => 'ShiftOptions.name',
					'ShiftId' => 'ShiftOptions.id',
					'StartTime' => 'InstitutionShifts.start_time',
					'EndTime' => 'InstitutionShifts.end_time'
				])
				->where([
					'OR' => [
						[$this->InstitutionShifts->aliasField('location_institution_id') => $institutionId],
						[$this->InstitutionShifts->aliasField('institution_id') => $institutionId]
					],
					$this->InstitutionShifts->aliasField('academic_period_id') => $academicPeriod
				])
				->toArray();

		return $data;
	}

	public function onUpdateDefaultActions(Event $event) {
		return ['downloadFile'];
	}

	public function onUpdateFieldDateClosed(Event $event, array $attr, $action, Request $request)
	{
		$attr['default_date'] = false;
		return $attr;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->isNew()) {
			$entity->shift_type = 0;
		}
	}

	public function beforeAction($event) {
		$this->ControllerAction->field('security_group_id', ['visible' => false]);
		// $this->ControllerAction->field('institution_site_area_id', ['visible' => false]);
		$this->ControllerAction->field('date_closed');
		$this->ControllerAction->field('modified', ['visible' => false]);
		$this->ControllerAction->field('modified_user_id', ['visible' => false]);
		$this->ControllerAction->field('created', ['visible' => false]);
		$this->ControllerAction->field('created_user_id', ['visible' => false]);

		$this->ControllerAction->field('institution_locality_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_ownership_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_status_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_sector_id', ['type' => 'select', 'onChangeReload' => true]);
		if ($this->action == 'index' || $this->action == 'view') {
			$this->ControllerAction->field('institution_provider_id', ['type' => 'select']);
		}
		$this->ControllerAction->field('institution_gender_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_network_connectivity_id', ['type' => 'select']);
		$this->ControllerAction->field('area_administrative_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives', 'displayCountry' => false]);
		$this->ControllerAction->field('area_id', ['type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);

		$this->ControllerAction->field('information_section', ['type' => 'section', 'title' => __('Information')]);

		$this->ControllerAction->field('shift_section', ['type' => 'section', 'title' => __('Shifts'), 'visible' => ['view'=>true]]);
		$this->ControllerAction->field('shift_type', ['visible' => ['view' => true]]);

		$this->ControllerAction->field('shift_details', [
			'type' => 'element',
			'element' => 'Institution.Shifts/details',
			'visible' => ['view'=>true],
			'data' => $this->getViewShiftDetail($this->Session->read('Institution.Institutions.id'), $this->InstitutionShifts->AcademicPeriods->getCurrent())
		]);

		$this->ControllerAction->field('location_section', ['type' => 'section', 'title' => __('Location')]);

		$language = I18n::locale();
		$field = 'area_id';
		$areaLabel = $this->onGetFieldLabel($event, $this->alias(), $field, $language, true);
		$this->ControllerAction->field('area_section', ['type' => 'section', 'title' => $areaLabel]);
		$field = 'area_administrative_id';
		$areaAdministrativesLabel = $this->onGetFieldLabel($event, $this->alias(), $field, $language, true);
		$this->ControllerAction->field('area_administrative_section', ['type' => 'section', 'title' => $areaAdministrativesLabel]);
		$this->ControllerAction->field('contact_section', ['type' => 'section', 'title' => __('Contact')]);
		$this->ControllerAction->field('map_section', ['type' => 'section', 'title' => __('Map'), 'visible' => ['view'=>true]]);
		$this->ControllerAction->field('map', ['type' => 'map', 'visible' => ['view'=>true]]);

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}

		if ($this->action == 'view' || $this->action == 'edit') {
			// Moved to InstitutionContacts
			$this->ControllerAction->field('contact_section', ['visible' => false]);
			$this->ControllerAction->field('contact_person', ['visible' => false]);
	        $this->ControllerAction->field('telephone', ['visible' => false]);
	        $this->ControllerAction->field('fax', ['visible' => false]);
	        $this->ControllerAction->field('email', ['visible' => false]);
	        $this->ControllerAction->field('website', ['visible' => false]);
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$SecurityGroup = TableRegistry::get('Security.SystemGroups');
		$SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');

		$dispatchTable = [];
		$dispatchTable[] = $SecurityGroup;
		$dispatchTable[] = $this->ExaminationCentres;
		$dispatchTable[] = $SecurityGroupAreas;

		foreach ($dispatchTable as $model) {
			$model->dispatchEvent('Model.Institutions.afterSave', [$entity], $this);
		}
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$securityGroupId = $entity->security_group_id;
		$SecurityGroup = TableRegistry::get('Security.SystemGroups');

		$groupEntity = $SecurityGroup->get($securityGroupId);
		$SecurityGroup->delete($groupEntity);
	}

	public function afterAction(Event $event, ArrayObject $config) {
		if ($this->action == 'index') {
			$institutionCount = $this->find();
			$conditions = [];

			$institutionCount = clone $this->dashboardQuery;
			$cloneClass = clone $this->dashboardQuery;

			$models = [
				['Types', 'institution_type_id', 'Type', 'query' => $this->dashboardQuery],
				['Sectors', 'institution_sector_id', 'Sector', 'query' => $this->dashboardQuery],
				['Localities', 'institution_locality_id', 'Locality', 'query' => $this->dashboardQuery],
			];

			foreach ($models as $key => $model) {
				$institutionArray[$key] = $this->getDonutChart('institutions', $model);
			}

			$indexDashboard = 'dashboard';
			$count = $institutionCount->count();
			unset($institutionCount);
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'institutions',
	            	'modelCount' => $count,
	            	'modelArray' => $institutionArray,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	    $config['formButtons'] = false;
	}

	public function getNumberOfInstitutionsByModel($params=[]) {

		if (!empty($params)) {
			$query = $params['query'];

			$modelName = $params[0];
			$modelId = $params[1];
			$key = $params[2];
			$params['key'] = __($key);

			$institutionRecords = clone $query;

			$selectString = $modelName.'.name';
			$institutionTypesCount = $institutionRecords
				->contain([$modelName])
				->select([
					'count' => $institutionRecords->func()->count($modelId),
					'name' => $selectString
				])
				->group($modelId)
				;

			$this->advancedSearchQuery($this->request, $institutionTypesCount);

			// Creating the data set
			$dataSet = [];
			foreach ($institutionTypesCount->toArray() as $key => $value) {
	            // Compile the dataset
				$dataSet[] = [__($value['name']), $value['count']];
			}
			$params['dataSet'] = $dataSet;
		}
		unset($institutionRecords);
		return $params;
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforeAction(Event $event) {
		$this->Session->delete('Institutions.id');

		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'area_id', 'institution_type_id'
		]);

		$this->ControllerAction->setFieldVisible(['index'], [
			'code', 'name', 'area_id', 'institution_type_id'
		]);
		$this->controller->set('ngController', 'AdvancedSearchCtrl');
	}

	public function onGetAreaId(Event $event, Entity $entity) {
		if($this->action == 'index'){
			$areaName = $entity->Areas['name'];
			// Getting the system value for the area
			$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
			$areaLevel = $ConfigItems->value('institution_area_level_id');

			// Getting the current area id
			$areaId = $entity->area_id;
			try {
				if ($areaId > 0) {
					$path = $this->Areas
						->find('path', ['for' => $areaId])
						->toArray();

					foreach($path as $value){
						if ($value['area_level_id'] == $areaLevel) {
							$areaName = $value['name'];
						}
					}
				}
			} catch (InvalidPrimaryKeyException $ex) {
				$this->log($ex->getMessage(), 'error');
			}
			return $areaName;
		}
		return $entity->area_id;
	}

	public function onGetAreaAdministrativeId(Event $event, Entity $entity) {
		if ($this->action == 'view') {
			return $entity->area_administrative_id;
		}
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
			if ($field == 'area_id' && $this->action == 'index') {
				// Getting the system value for the area
				$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
				$areaLevel = $ConfigItems->value('institution_area_level_id');

				$AreaTable = TableRegistry::get('Area.AreaLevels');
				return $AreaTable->get($areaLevel)->name;
			} else {
				return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
			}
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		// the query options are setup so that Security.InstitutionBehavior can reuse it
		$options['query'] = [
			'contain' => ['Types'],
			'select' => [
				$this->aliasField('id'), $this->aliasField('code'), $this->aliasField('name'),
				$this->aliasField('area_id'), 'Areas.name', 'Types.name'
			],
			'join' => [
				[
					'table' => 'areas', 'alias' => 'Areas', 'type' => 'INNER',
					'conditions' => ['Areas.id = ' . $this->aliasField('area_id')]
				]
			]
		];
		$options['auto_contain'] = false;
		$query->contain($options['query']['contain']);
		$query->select($options['query']['select']);
		$query->join($options['query']['join']);

		$queryParams = $request->query;
		$options['order'] = [$this->aliasField('name') => 'asc'];
	}

	public function indexAfterPaginate(Event $event, ResultSet $resultSet, Query $query) {
		$this->dashboardQuery = clone $query;
	}

	public function indexAfterAction(Event $event, $data) {
		$search = $this->ControllerAction->getSearchKey();
		if (empty($search)) {
			// redirect to school dashboard if it is only one record and no add access
			$addAccess = $this->AccessControl->check(['Institutions', 'add']);
			if ($data->count() == 1 && !$addAccess) {
				$entity = $data->first();
				$event->stopPropagation();
				$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'dashboard', $entity->id];
				return $this->controller->redirect($action);
			}
		}
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'information_section',
			'name', 'alternative_name', 'code', 'is_academic', 'institution_sector_id', 'institution_provider_id', 'institution_type_id',
			'institution_ownership_id', 'institution_gender_id', 'institution_network_connectivity_id', 'institution_status_id', 'date_opened', 'date_closed',

			'shift_section',
			'shift_type', 'shift_details',

			'location_section',
			'address', 'postal_code', 'institution_locality_id', 'latitude', 'longitude',

			'area_section',
			'area_id',

			'area_administrative_section',
			'area_administrative_id',

			'map_section',
			'map',
		]);
	}

/******************************************************************************************************************
**
** add / addEdit action methods
**
******************************************************************************************************************/

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'information_section',
			'name', 'alternative_name', 'code', 'is_academic', 'institution_sector_id', 'institution_provider_id', 'institution_type_id',
			'institution_ownership_id', 'institution_gender_id', 'institution_network_connectivity_id', 'institution_status_id', 'date_opened', 'date_closed',

			'location_section',
			'address', 'postal_code', 'institution_locality_id', 'latitude', 'longitude',

			'area_section',
			'area_id',

			'area_administrative_section',
			'area_administrative_id',

			'contact_section',
			'contact_person', 'telephone', 'fax', 'email', 'website',
		]);
	}

		public function editBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'information_section',
			'name', 'alternative_name', 'code', 'institution_sector_id', 'institution_provider_id',  'institution_type_id',
			'institution_ownership_id', 'institution_gender_id', 'institution_network_connectivity_id', 'institution_status_id', 'date_opened', 'date_closed',

			'location_section',
			'address', 'postal_code', 'institution_locality_id', 'latitude', 'longitude',

			'area_section',
			'area_id',

			'area_administrative_section',
			'area_administrative_id',
		]);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('institution_type_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_provider_id', ['type' => 'select', 'sectorId' => $entity->institution_sector_id]);
		$this->ControllerAction->field('is_academic', ['type' => 'select', 'options' => [], 'entity' => $entity, 'after' => 'code']);
	}

	public function viewAfterAction(Event $event, Entity $entity)
	{
		$this->ControllerAction->field('is_academic', ['type' => 'select', 'options' => [], 'entity' => $entity, 'after' => 'code']);
	}

	public function onUpdateFieldInstitutionProviderId(Event $event, array $attr, $action, Request $request) {
		$providerOptions = [];
		$selectedSectorId = '';

		if (isset($request->data[$this->alias()]['institution_sector_id'])) {
            $selectedSectorId = $request->data[$this->alias()]['institution_sector_id'];

        } else if ($action == 'add') {
        	$SectorTable = $this->Sectors;
        	$defaultSector = $SectorTable
        		->find()
        		->where([$SectorTable->aliasField('default') => 1])
        		->first();

        	if(!empty($defaultSector)) {
        		$selectedSectorId = $defaultSector->id;
        	}

        } else if ($action == 'edit') {
        	$selectedSectorId = $attr['sectorId'];
        }

        if (!empty($selectedSectorId)) {
	        $ProviderTable = $this->Providers;
	        $providerOptions = $ProviderTable->find('list')
				->where([$ProviderTable->aliasField('institution_sector_id') => $selectedSectorId])
				->toArray();
		}

        $attr['options'] = $providerOptions;
        $attr['empty'] = true;
        return $attr;
    }

/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/

	// autocomplete used for UserGroups
	public function autocomplete($search, $params = []) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$search = sprintf('%s%%', $search);

		$list = $this
			->find()
			->where([
				'OR' => [
					$this->aliasField('name') . ' LIKE' => $search,
					$this->aliasField('code') . ' LIKE' => $search
				]
			])
			->where([$conditions])
			->order([$this->aliasField('name')])
			->all();

		$data = array();
		foreach($list as $obj) {
			$data[] = [
				'label' => sprintf('%s (%s)', $obj->name, $obj->code),
				'value' => $obj->id
			];
		}
		return $data;
	}

	public function onUpdateFieldInstitutionTypeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			// list($typeOptions, $selectedType) = array_values($this->getTypeOptions());

			// $attr['options'] = $typeOptions;
			$attr['onChangeReload'] = 'changeType';
		}

		return $attr;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (!$this->AccessControl->isAdmin()) {
			$userId = $this->Auth->user('id');
			$institutionId = $entity->id;
			$securityRoles = $this->getInstitutionRoles($userId, $institutionId);
			foreach ($buttons as $key => $b) {
				$url = $this->ControllerAction->url($key);
				if (!$this->AccessControl->check($url, $securityRoles)) {
					unset($buttons[$key]);
				}
			}
		}
		return $buttons;
	}

	public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('institution_type_id', $request->data[$this->alias()])) {
					$selectedType = $request->data[$this->alias()]['institution_type_id'];
					$entity->institution_type_id = $selectedType;
				}
			}
		}
	}

	public function getTypeOptions() {
		$typeOptions = $this->Types->getList()->toArray();
		$selectedType = $this->Types->getDefaultValue();

		// $selectedType = $this->queryString('type', $typeOptions);
		// $this->advancedSelectOptions($typeOptions, $selectedType);
		// , ['default' => $typeDefault]

		return compact('typeOptions', 'selectedType');
	}

/******************************************************************************************************************
**
** Security Functions
**
******************************************************************************************************************/

	public function onUpdateFieldIsAcademic(Event $event, array $attr, $action, Request $request) {

		if ($action == 'add') {
			$attr['select'] = false;
			$attr['options'] = $this->isAcademicOptions;
		} else if ($action == 'edit') {
			$attr['type'] = 'disabled';
			$attr['attr']['value'] = __($this->isAcademicOptions[$attr['entity']->is_academic]);
		}
		return $attr;
	}

	public function onGetIsAcademic(Event $event, Entity $entity)
	{
		$selectedIsAcademic = $entity->is_academic;
		return __($this->isAcademicOptions[$selectedIsAcademic]);
	}

	/**
	 * To get the list of security group id for the particular institution and user
	 *
	 * @param integer $userId User Id
	 * @param integer $institutionId Institution id
	 * @return array The list of security group id that the current user for access to the institution
	 */
	public function getSecurityGroupId($userId, $institutionId) {
		$institutionEntity = $this->get($institutionId);

		// Get parent of the area and the current area
		$areaId = $institutionEntity->area_id;
		$Areas = $this->Areas;
		$institutionArea = $Areas->get($areaId);

		// Getting the security groups
		$SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
		$SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
		$securityGroupIds = $SecurityGroupAreas->find()
			->innerJoinWith('Areas')
			->innerJoinWith('SecurityGroups.Users')
			->where([
				'Areas.lft <= ' => $institutionArea->lft,
				'Areas.rght >= ' => $institutionArea->rght,
				'Users.id' => $userId
			])
			->union(
				$SecurityGroupInstitutions->find()
					->innerJoinWith('SecurityGroups.Users')
					->where([
						$SecurityGroupInstitutions->aliasField('institution_id') => $institutionId,
						'Users.id' => $userId
					])
					->select([$SecurityGroupInstitutions->aliasField('security_group_id')])
					->distinct([$SecurityGroupInstitutions->aliasField('security_group_id')])
			)
			->select([$SecurityGroupAreas->aliasField('security_group_id')])
			->distinct([$SecurityGroupAreas->aliasField('security_group_id')])
			->hydrate(false)
			->toArray();
		$securityGroupIds = $this->array_column($securityGroupIds, 'security_group_id');
		return $securityGroupIds;
	}

	/**
	 * To list of roles that are authorised for access to a particular institution
	 *
	 * @param integer $userId User Id
	 * @param integer $institutionId Institution id
	 * @return array The list of security roles id that the current user for access to the institution
	 */
	public function getInstitutionRoles($userId, $institutionId) {
		$groupIds = $this->getSecurityGroupId($userId, $institutionId);
		$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
		return $SecurityGroupUsers->getRolesByUserAndGroup($groupIds, $userId);
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
    	$extra['excludedModels'] = [
    		$this->SecurityGroups->alias(), $this->InstitutionSurveys->alias(), $this->StudentSurveys->alias(),
    		$this->StaffPositionProfiles->alias(), $this->InstitutionActivities->alias(), $this->StudentPromotion->alias(),
    		$this->StudentAdmission->alias(), $this->StudentDropout->alias(), $this->TransferApprovals->alias(),
            $this->CustomFieldValues->alias(), $this->CustomTableCells->alias()
    	];
    }

	public function getCustomFilter(Event $event)
	{
		$filters['shift_type'] = [
			'label' => __('Shift Type'),
			'options' => $this->shiftTypes
		];
		return $filters;
	}

	public function findNotExamCentres(Query $query, array $options)
	{
		if (isset($options['examination_id'])) {
			$query
				->leftJoinWith('ExaminationCentres', function($q) use ($options) {
					return $q
						->where(['ExaminationCentres.examination_id' => $options['examination_id']]);
				})
				->where([
					'ExaminationCentres.institution_id IS NULL'
				])
				;
			return $query;
		}
	}
}
