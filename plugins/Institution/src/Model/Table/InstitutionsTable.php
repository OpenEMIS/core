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
use Cake\ORM\ResultSet;

use App\Model\Table\AppTable;

class InstitutionsTable extends AppTable  {
	private $dashboardQuery = null;

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

		$this->hasMany('InstitutionActivities', 			['className' => 'Institution.InstitutionActivities', 'dependent' => true]);
		$this->hasMany('InstitutionAttachments', 			['className' => 'Institution.InstitutionAttachments', 'dependent' => true]);

		$this->hasMany('InstitutionPositions', 				['className' => 'Institution.InstitutionPositions', 'dependent' => true]);
		$this->hasMany('InstitutionShifts', 				['className' => 'Institution.InstitutionShifts', 'dependent' => true]);
		$this->hasMany('InstitutionSections', 				['className' => 'Institution.InstitutionSections', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionClasses', 				['className' => 'Institution.InstitutionClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('Infrastructures',					['className' => 'Institution.InstitutionInfrastructures', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->hasMany('Staff',				 				['className' => 'Institution.Staff', 'dependent' => true]);
		$this->hasMany('StaffBehaviours', 					['className' => 'Institution.StaffBehaviours', 'dependent' => true]);
		$this->hasMany('InstitutionStaffAbsences', 			['className' => 'Institution.StaffAbsences', 'dependent' => true]);

		$this->hasMany('Students', 							['className' => 'Institution.Students', 'dependent' => true]);
		$this->hasMany('StudentBehaviours', 				['className' => 'Institution.StudentBehaviours', 'dependent' => true]);
		$this->hasMany('InstitutionStudentAbsences', 		['className' => 'Institution.InstitutionStudentAbsences', 'dependent' => true]);

		$this->hasMany('InstitutionBankAccounts', 			['className' => 'Institution.InstitutionBankAccounts', 'dependent' => true]);
		$this->hasMany('InstitutionFees', 					['className' => 'Institution.InstitutionFees', 'dependent' => true]);

		$this->hasMany('InstitutionGrades', 				['className' => 'Institution.InstitutionGrades', 'dependent' => true]);
		
		$this->hasMany('StudentPromotion', 					['className' => 'Institution.StudentPromotion', 'dependent' => true]);

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
        $this->addBehavior('Area.Areapicker', [
        	'display_country' => false
        ]);
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('OpenEmis.Map');
        $this->addBehavior('HighChart', ['institutions' => ['_function' => 'getNumberOfInstitutionsByModel']]);
        $this->addBehavior('Import.ImportLink');

	}

	public function validationDefault(Validator $validator) {
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
					'ruleUnique' => [
		        		'rule' => 'validateUnique',
		        		'provider' => 'table',
		        		// 'message' => 'Email has to be unique',
		        		'last' => true
				    ],
					'ruleValidEmail' => [
						'rule' => 'email'
					]
				])
			->add('area_id', 'ruleAuthorisedArea', [
					'rule' => 'checkAuthorisedArea'
				])
	        ;
		return $validator;
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

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$query
			->contain(['Areas'])
			->select(['area_code' => 'Areas.code']);
	}

	public function onGetName(Event $event, Entity $entity) {
		$name = $entity->name;

		if ($this->AccessControl->check([$this->controller->name, 'dashboard'])) {
			$name = $event->subject()->Html->link($entity->name, [
				'plugin' => $this->controller->plugin,
				'controller' => $this->controller->name,
				'action' => 'dashboard',
				'0' => $entity->id
			]);
		}
		
		return $name;
	}

	public function beforeAction($event) {
		$this->ControllerAction->field('security_group_id', ['visible' => false]);
		// $this->ControllerAction->field('institution_site_area_id', ['visible' => false]);
		$this->ControllerAction->field('modified', ['visible' => false]);
		$this->ControllerAction->field('modified_user_id', ['visible' => false]);
		$this->ControllerAction->field('created', ['visible' => false]);
		$this->ControllerAction->field('created_user_id', ['visible' => false]);

		$this->ControllerAction->field('institution_type_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_locality_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_ownership_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_status_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_sector_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_provider_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_gender_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_network_connectivity_id', ['type' => 'select']);
		$this->ControllerAction->field('area_administrative_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);
		$this->ControllerAction->field('area_id', ['type' => 'areapicker', 'source_model' => 'Area.Areas']);

		$this->ControllerAction->field('information_section', ['type' => 'section', 'title' => __('Information')]);
		$this->ControllerAction->field('location_section', ['type' => 'section', 'title' => __('Location')]);
		$this->ControllerAction->field('area_section', ['type' => 'section', 'title' => __('Area')]);
		$this->ControllerAction->field('contact_section', ['type' => 'section', 'title' => __('Contact')]);
		$this->ControllerAction->field('map_section', ['type' => 'section', 'title' => __('Map'), 'visible' => ['view'=>true]]);
		$this->ControllerAction->field('map', ['type' => 'map', 'visible' => ['view'=>true]]);

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$SecurityGroup = TableRegistry::get('Security.SystemGroups');
		$SecurityInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');

        if ($entity->isNew()) {
			$obj = $SecurityGroup->newEntity(['name' => $entity->name]);
			$securityGroup = $SecurityGroup->save($obj);
			if ($securityGroup) {
				// add the relationship of security group and institutions
				$securityInstitution = $SecurityInstitutions->newEntity([
					'security_group_id' => $securityGroup->id, 
					'institution_id' => $entity->id
				]);
				$SecurityInstitutions->save($securityInstitution);

				$this->trackActivity = false;
				$entity->security_group_id = $securityGroup->id;
				if (!$this->save($entity)) {
					return false;
				}
			} else {
				return false;
			}

        } else {
			$securityGroupId = $entity->security_group_id;
			if (!empty($securityGroupId)) {
				$obj = $SecurityGroup->get($securityGroupId);
				if (is_object($obj)) {
					$data = ['name' => $entity->name];
					$obj = $SecurityGroup->patchEntity($obj, $data);
					$securityGroup = $SecurityGroup->save($obj);
					if (!$securityGroup) {
						return false;
					}
				}
			}

        }
        return true;
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
	}

	public function onGetAreaId(Event $event, Entity $entity) {
		$areaName = $entity->Areas['name'];

		if($this->action == 'index'){
			// Getting the system value for the area
			$ConfigItems = TableRegistry::get('ConfigItems');
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
		}

		return $areaName;
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
			if ($field == 'area_id' && $this->action == 'index') {
				// Getting the system value for the area
				$ConfigItems = TableRegistry::get('ConfigItems');
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
		if (!array_key_exists('sort', $queryParams) && !array_key_exists('direction', $queryParams)) {
			$query->order([$this->aliasField('name') => 'asc']);
		}
	}

	public function indexAfterPaginate(Event $event, ResultSet $resultSet) {
		$query = $resultSet->__debugInfo()['query'];
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
			'name', 'alternative_name', 'code', 'institution_provider_id', 'institution_sector_id', 'institution_type_id', 
			'institution_ownership_id', 'institution_gender_id', 'institution_network_connectivity_id', 'institution_status_id', 'date_opened', 'date_closed',
			
			'location_section',
			'address', 'postal_code', 'institution_locality_id', 'latitude', 'longitude',

			'area_section',
			'area_id', 'area_administrative_id',

			'contact_section',
			'contact_person', 'telephone', 'fax', 'email', 'website',

			'map_section',
			'map',

		]);
	}


/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/
	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'information_section',
			'name', 'alternative_name', 'code', 'institution_provider_id', 'institution_sector_id', 'institution_type_id', 
			'institution_ownership_id', 'institution_gender_id', 'institution_network_connectivity_id', 'institution_status_id', 'date_opened', 'date_closed',
			
			'location_section',
			'address', 'postal_code', 'institution_locality_id', 'latitude', 'longitude',

			'area_section',
			'area_id', 'area_administrative_id',

			'contact_section',
			'contact_person', 'telephone', 'fax', 'email', 'website',
		]);
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	
	// autocomplete used for UserGroups
	public function autocomplete($search, $params = []) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$search = sprintf('%%%s%%', $search);

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
			$attr['onChangeReload'] = true;
		}

		return $attr;
	}
}
